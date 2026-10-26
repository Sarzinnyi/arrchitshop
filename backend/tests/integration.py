"""Run against an isolated SQLite database: PHP_BIN=php python3 backend/tests/integration.py."""
import base64
import http.cookiejar
import json
import os
from pathlib import Path
import secrets
import socket
import sqlite3
import subprocess
import tempfile
import time
import unittest
import urllib.error
import urllib.request

ROOT = Path(__file__).resolve().parents[1]
PHP = os.environ.get('PHP_BIN', 'php')
IMAGE = base64.b64decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+a0ZkAAAAASUVORK5CYII=')

class API(unittest.TestCase):
    @classmethod
    def setUpClass(cls):
        cls.temp = tempfile.TemporaryDirectory()
        cls.password = secrets.token_urlsafe(24)
        cls.database = str(Path(cls.temp.name) / 'test.sqlite')
        cls.mysql = bool(os.environ.get('TEST_MYSQL_DSN'))
        env = {**os.environ, 'DB_DSN': os.environ.get('TEST_MYSQL_DSN') or 'sqlite:' + cls.database, 'DB_USER': os.environ.get('TEST_MYSQL_USER', ''), 'DB_PASSWORD': os.environ.get('TEST_MYSQL_PASSWORD', ''), 'ADMIN_EMAIL': 'test@example.test', 'ADMIN_PASSWORD': cls.password, 'ADMIN_NAME': 'Test owner'}
        cls.database_env = env
        subprocess.run([PHP, str(ROOT / 'bin/setup.php'), '--create-admin'], env=env, check=True, stdout=subprocess.DEVNULL)
        with socket.socket() as sock:
            sock.bind(('127.0.0.1', 0))
            port = sock.getsockname()[1]
        cls.base = f'http://127.0.0.1:{port}'
        cls.log = open(Path(cls.temp.name) / 'server.log', 'w+')
        cls.server = subprocess.Popen([PHP, '-d', 'upload_max_filesize=5M', '-d', 'post_max_size=65M', '-S', f'127.0.0.1:{port}', '-t', str(ROOT / 'public'), str(ROOT / 'public/index.php')], env=env, stdout=cls.log, stderr=cls.log)
        for _ in range(100):
            try:
                urllib.request.urlopen(cls.base + '/api/session', timeout=1).close()
                break
            except (OSError, urllib.error.URLError):
                time.sleep(.05)
        else:
            raise RuntimeError('Test server did not start')

    @classmethod
    def tearDownClass(cls):
        cls.server.terminate()
        cls.server.wait(timeout=5)
        for row in cls.sql('SELECT path FROM photos'):
            (ROOT / 'public' / row['path'].lstrip('/')).unlink(missing_ok=True)
        cls.log.close()
        cls.temp.cleanup()

    @classmethod
    def sql(cls, statement):
        if cls.mysql:
            result = subprocess.run([PHP, '-r', 'require $argv[1]; echo json_encode(query($argv[2])->fetchAll());', str(ROOT / 'bootstrap.php'), statement], env=cls.database_env, capture_output=True, check=True, text=True)
            return json.loads(result.stdout)
        with sqlite3.connect(cls.database) as db:
            db.row_factory = sqlite3.Row
            return [dict(row) for row in db.execute(statement).fetchall()]

    def setUp(self):
        self.client = urllib.request.build_opener(urllib.request.HTTPCookieProcessor(http.cookiejar.CookieJar()))
        self.csrf = self.request('/session')[1]['csrf']

    def request(self, path, method='GET', data=None, csrf=True, multipart=None):
        headers = {}
        if csrf:
            headers['X-CSRF-Token'] = self.csrf if hasattr(self, 'csrf') else ''
        body = None
        if multipart is not None:
            boundary = 'test' + secrets.token_hex(16)
            body = b''
            for name, content, filename in multipart:
                body += f'--{boundary}\r\nContent-Disposition: form-data; name="{name}"'.encode()
                if filename:
                    body += f'; filename="{filename}"\r\nContent-Type: image/png'.encode()
                body += b'\r\n\r\n' + content + b'\r\n'
            body += f'--{boundary}--\r\n'.encode()
            headers['Content-Type'] = f'multipart/form-data; boundary={boundary}'
        elif data is not None:
            body = json.dumps(data).encode()
            headers['Content-Type'] = 'application/json'
        request = urllib.request.Request(self.base + '/api' + path, data=body, method=method, headers=headers)
        try:
            response = self.client.open(request)
        except urllib.error.HTTPError as e:
            response = e
        with response:
            return response.status, json.load(response), response.headers

    def login(self):
        status, data, _ = self.request('/login', 'POST', {'email': 'test@example.test', 'password': self.password})
        self.assertEqual(status, 200, data)
        self.csrf = data['csrf']

    def test_authentication_csrf_and_logout(self):
        self.assertEqual(self.request('/products')[0], 401)
        self.assertEqual(self.request('/login', 'POST', {'email': 'test@example.test', 'password': self.password}, csrf=False)[0], 403)
        old = self.csrf
        self.login()
        self.assertNotEqual(old, self.csrf)
        self.assertEqual(self.request('/session')[1]['admin']['email'], 'test@example.test')
        self.assertEqual(self.request('/sizes', 'POST', {'label': 'Unauthorized'}, csrf=False)[0], 403)
        self.assertEqual(self.request('/logout', 'POST')[0], 200)
        self.assertEqual(self.request('/products')[0], 401)

    def test_product_variants_photos_and_custom_options(self):
        self.login()
        options = self.request('/options')[1]
        self.assertTrue({'S', 'M', 'L', '28', '29', '30'} <= {x['label'] for x in options['sizes']})
        status, size, _ = self.request('/sizes', 'POST', {'label': '32'})
        self.assertEqual(status, 201)
        self.assertEqual(self.request('/sizes', 'POST', {'label': '32'})[0], 409)
        status, color, _ = self.request('/colors', 'POST', {'name': 'Dusty blue', 'hex': '#7799aa'})
        self.assertEqual(status, 201)
        default_color = options['colors'][0]['id']
        d = {'p_id': 'TEST-001', 'name': 'Linen shirt', 'price': '49.90', 'description': 'Relaxed fit', 'variants': [{'color_id': default_color, 'size_id': options['sizes'][0]['id'], 'quantity': 7}, {'color_id': color['id'], 'size_id': size['id'], 'quantity': 3}], 'photos': [{'key': 'new:0', 'color_id': None}, {'key': 'new:1', 'color_id': color['id']}]}
        def save(value, files=True):
            parts = [('product', json.dumps(value).encode(), None)]
            if files:
                parts += [('photos[]', IMAGE, 'cover.png'), ('photos[]', IMAGE, 'blue.png')]
            return self.request('/products', 'POST', multipart=parts)
        status, data, _ = save(d)
        self.assertEqual(status, 201, data)
        p = data['product']
        self.assertEqual(len(p['variants']), 2)
        self.assertEqual(sum(v['quantity'] for v in p['variants']), 10)
        self.assertEqual(len(p['photos']), 2)
        self.assertEqual(p['photos'][0]['position'], 0)
        with self.client.open(self.base + p['photos'][0]['path']) as photo:
            self.assertEqual(photo.headers['Content-Type'], 'image/png')
            self.assertEqual(photo.read(), IMAGE)
        self.assertEqual(save(d)[0], 409)
        d['id'] = p['id']
        d['variants'].append(d['variants'][0])
        self.assertEqual(save(d)[0], 422)
        d['variants'].pop()
        d['variants'][0]['quantity'] = -1
        self.assertEqual(save(d)[0], 422)
        d['variants'][0]['quantity'] = 12
        d['photos'] = [{'key': f"old:{photo['id']}", 'color_id': photo['color_id']} for photo in reversed(p['photos'])]
        status, updated, _ = save(d, False)
        self.assertEqual(status, 200, updated)
        self.assertEqual(updated['product']['photos'][0]['path'], p['photos'][1]['path'])
        self.assertEqual(updated['product']['variants'][0]['quantity'], 12)
        self.assertEqual(self.request(f"/products/{p['id']}", 'DELETE')[0], 200)
        for photo in p['photos']:
            self.assertFalse((ROOT / 'public' / photo['path'].lstrip('/')).exists())
        self.assertEqual(self.request('/products')[1]['products'], [])

    def test_reject_non_image_upload(self):
        self.login()
        options = self.request('/options')[1]
        product = {'p_id': 'BAD-IMAGE', 'name': 'Test', 'price': '20', 'variants': [{'color_id': options['colors'][0]['id'], 'size_id': options['sizes'][0]['id'], 'quantity': 1}], 'photos': [{'key': 'new:0', 'color_id': None}]}
        status, _, _ = self.request('/products', 'POST', multipart=[('product', json.dumps(product).encode(), None), ('photos[]', b'<?php echo "bad";', 'fake.png')])
        self.assertEqual(status, 422)

    def test_rate_limit(self):
        for _ in range(5):
            self.assertEqual(self.request('/login', 'POST', {'email': 'unknown@example.test', 'password': 'wrong'})[0], 401)
        status, _, headers = self.request('/login', 'POST', {'email': 'unknown@example.test', 'password': 'wrong'})
        self.assertEqual(status, 429)
        self.assertGreater(int(headers['Retry-After']), 0)
        # An account bucket expires after 15 minutes; IP throttling is independent.
        self.sql('UPDATE login_limits SET expires_at=0 WHERE attempts>5')
        self.assertEqual(self.request('/login', 'POST', {'email': 'unknown@example.test', 'password': 'wrong'})[0], 401)

if __name__ == '__main__':
    unittest.main(verbosity=2)
