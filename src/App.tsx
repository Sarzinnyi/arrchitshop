import { useEffect, useState, useRef } from "react";
import type { FormEvent } from "react";
import {
  FiPlus,
  FiSearch,
  FiGrid,
  FiLayers,
  FiLogOut,
  FiX,
  FiArrowUp,
  FiArrowDown,
  FiTrash2,
  FiEdit2,
  FiPackage,
  FiArrowUpRight,
  FiImage,
  FiCheck,
  FiMenu,
} from "react-icons/fi";

type Admin = { id: number; name: string; email: string };
type Size = { id: number; label: string };
type Color = { id: number; name: string; hex: string };
type Variant = {
  size_id: number;
  color_id: number;
  quantity: number;
  size?: string;
  color?: string;
  hex?: string;
};
type Photo = {
  id: number;
  path: string;
  color_id: number | null;
  position: number;
};
type Product = {
  id: number;
  p_id: string;
  name: string;
  description: string;
  price: string;
  variants: Variant[];
  photos: Photo[];
};
type DraftPhoto = {
  key: string;
  url: string;
  color_id: number | null;
  file?: File;
};
let csrf = "";
async function api<T>(path: string, options: RequestInit = {}): Promise<T> {
  const res = await fetch(`/api${path}`, {
    ...options,
    headers: {
      ...(options.body instanceof FormData
        ? {}
        : { "Content-Type": "application/json" }),
      "X-CSRF-Token": csrf,
      ...options.headers,
    },
  });
  const data = await res.json();
  if (!res.ok)
    throw new Error(data.error || "Something went wrong. Please try again.");
  return data as T;
}
const money = (price: string | number) =>
  new Intl.NumberFormat("en-US", { style: "currency", currency: "USD" }).format(
    Number(price),
  );
const stock = (p: Product) =>
  p.variants.reduce((n, v) => n + Number(v.quantity), 0);

export default function App() {
  const [admin, setAdmin] = useState<Admin | null>(null);
  const [ready, setReady] = useState(false);
  const [error, setError] = useState("");
  const [products, setProducts] = useState<Product[]>([]);
  const [sizes, setSizes] = useState<Size[]>([]);
  const [colors, setColors] = useState<Color[]>([]);
  const [tab, setTab] = useState("Products");
  const [search, setSearch] = useState("");
  const [filter, setFilter] = useState("all");
  const [editor, setEditor] = useState<Product | "new" | null>(null);
  const [notice, setNotice] = useState("");
  const [menu, setMenu] = useState(false);
  const [loading, setLoading] = useState(false);
  const [deleting, setDeleting] = useState<Product | null>(null);
  const [busy, setBusy] = useState(false);
  useEffect(() => {
    let active = true;
    api<{ admin: Admin | null; csrf: string }>("/session")
      .then((data) => {
        csrf = data.csrf;
        if (active) {
          setAdmin(data.admin);
          setReady(true);
        }
      })
      .catch((e) => {
        if (active) {
          setError(e.message);
          setReady(true);
        }
      });
    return () => {
      active = false;
    };
  }, []);
  useEffect(() => {
    if (!admin) return;
    let active = true;
    Promise.all([
      api<{ products: Product[] }>("/products"),
      api<{ sizes: Size[]; colors: Color[] }>("/options"),
    ])
      .then(([p, o]) => {
        if (active) {
          setProducts(p.products);
          setSizes(o.sizes);
          setColors(o.colors);
        }
      })
      .catch((e) => {
        if (active) setError(e.message);
      });
    return () => {
      active = false;
    };
  }, [admin]);
  async function refresh() {
    setLoading(true);
    try {
      const [p, o] = await Promise.all([
        api<{ products: Product[] }>("/products"),
        api<{ sizes: Size[]; colors: Color[] }>("/options"),
      ]);
      setProducts(p.products);
      setSizes(o.sizes);
      setColors(o.colors);
    } finally {
      setLoading(false);
    }
  }
  async function logout() {
    try {
      await api("/logout", { method: "POST" });
      setAdmin(null);
      setProducts([]);
      setError("");
      const s = await api<{ csrf: string }>("/session");
      csrf = s.csrf;
    } catch (e) {
      setError((e as Error).message);
    }
  }
  if (!ready)
    return (
      <div className="loading-screen">
        <span className="brand-mark">a.</span>
        <p>Opening your studio…</p>
      </div>
    );
  if (!admin)
    return (
      <Login
        error={error}
        onLogin={(a) => {
          setError("");
          setAdmin(a);
        }}
      />
    );
  const filtered = products.filter(
    (p) =>
      `${p.name} ${p.p_id}`.toLowerCase().includes(search.toLowerCase()) &&
      (filter === "all" ||
        (filter === "low" ? stock(p) > 0 && stock(p) < 10 : stock(p) === 0)),
  );
  return (
    <div className="app-shell">
      <aside className={`sidebar ${menu ? "open" : ""}`}>
        <a
          className="brand"
          href="#"
          onClick={(e) => {
            e.preventDefault();
            setTab("Products");
          }}
        >
          <span className="brand-mark">a.</span>
          <span>
            arrchit<span className="brand-sub">SHOP STUDIO</span>
          </span>
        </a>
        <div className="workspace">
          <span className="workspace-icon">
            <FiPackage />
          </span>
          <div>
            My clothing store<small>Admin workspace</small>
          </div>
          <span className="live-dot" />
        </div>
        <div className="nav-label">WORKSPACE</div>
        <nav>
          {[
            ["Products", FiGrid],
            ["Sizes & colors", FiLayers],
          ].map(([label, Icon]) => {
            const NavIcon = Icon as typeof FiGrid;
            return (
              <button
                className={tab === label ? "active" : ""}
                key={label as string}
                onClick={() => {
                  setTab(label as string);
                  setMenu(false);
                  setError("");
                }}
              >
                <NavIcon />
                {label as string}
                {label === "Products" && (
                  <span className="nav-count">{products.length}</span>
                )}
              </button>
            );
          })}
        </nav>
        <div className="sidebar-note">
          <span>Made for your next collection.</span>
          <p>
            A little organization.
            <br />A lot of possibility.
          </p>
          <FiArrowUpRight />
        </div>
        <div className="account">
          <span className="avatar">{admin.name[0].toUpperCase()}</span>
          <div>
            {admin.name}
            <small>Store administrator</small>
          </div>
          <button className="icon-button" title="Sign out" onClick={logout}>
            <FiLogOut />
          </button>
        </div>
      </aside>
      <main>
        <header className="topbar">
          <button
            className="icon-button mobile-menu"
            onClick={() => setMenu(!menu)}
            aria-label="Toggle navigation"
          >
            <FiMenu />
          </button>
          <span>
            Workspace <span className="slash">/</span> <b>{tab}</b>
          </span>
          <span className="store-status">
            <span className="live-dot" /> Your store, organized
          </span>
        </header>
        <div className="page">
          <div className="page-heading">
            <div>
              <div className="eyebrow">YOUR COLLECTION, AT A GLANCE</div>
              <h1>
                {tab === "Products" ? "Products" : "Sizes & colors"}
                <span className="title-dot">.</span>
              </h1>
              <p>
                {tab === "Products"
                  ? "Good things deserve a place. Manage every piece in your collection."
                  : "The details that make every piece your own. Add options as your collection grows."}
              </p>
            </div>
            {tab === "Products" && (
              <button className="primary" onClick={() => setEditor("new")}>
                <FiPlus /> Add product
              </button>
            )}
          </div>
          {error && (
            <div className="alert" role="alert">
              {error}
              <button
                className="icon-button"
                onClick={() => setError("")}
                aria-label="Dismiss error"
              >
                <FiX />
              </button>
              <button
                onClick={() =>
                  refresh()
                    .then(() => setError(""))
                    .catch((e) => setError(e.message))
                }
              >
                Retry
              </button>
            </div>
          )}
          {notice && (
            <div className="notice" role="status">
              <FiCheck />
              {notice}
              <button
                className="icon-button"
                onClick={() => setNotice("")}
                aria-label="Dismiss notification"
              >
                <FiX />
              </button>
            </div>
          )}
          {tab === "Products" ? (
            <>
              <div className="stats">
                <Stat
                  label="Total products"
                  value={products.length}
                  detail="Pieces in your collection"
                  icon={<FiPackage />}
                />
                <Stat
                  label="Units in stock"
                  value={products.reduce((n, p) => n + stock(p), 0)}
                  detail="Across all sizes & colors"
                  icon={<FiLayers />}
                />
                <Stat
                  label="Inventory value"
                  value={money(
                    products.reduce(
                      (n, p) => n + stock(p) * Number(p.price),
                      0,
                    ),
                  )}
                  detail="Based on your retail prices"
                  icon={<FiArrowUpRight />}
                />
              </div>
              <section className="catalog">
                <div className="catalog-toolbar">
                  <div className="tabs">
                    {[
                      ["all", "All products"],
                      ["low", "Low stock"],
                      ["out", "Out of stock"],
                    ].map(([value, label]) => (
                      <button
                        key={value}
                        className={filter === value ? "selected" : ""}
                        onClick={() => setFilter(value)}
                      >
                        {label}
                        {value === "all" && <span>{products.length}</span>}
                      </button>
                    ))}
                  </div>
                  <label className="search">
                    <FiSearch />
                    <input
                      aria-label="Search products"
                      placeholder="Search name or product ID…"
                      value={search}
                      onChange={(e) => setSearch(e.target.value)}
                    />
                  </label>
                </div>
                <div className="collection-heading">
                  <span>
                    {filtered.length}{" "}
                    {filtered.length === 1 ? "product" : "products"}{" "}
                    <span className="muted">in your collection</span>
                  </span>
                  <button
                    className="text-button"
                    disabled={loading}
                    onClick={() => refresh().catch((e) => setError(e.message))}
                  >
                    {loading ? "Refreshing…" : "Refresh collection"}
                  </button>
                </div>
                {filtered.length ? (
                  <div className="product-grid">
                    {filtered.map((p) => (
                      <article className="product-card" key={p.id}>
                        <button
                          className="product-image"
                          onClick={() => setEditor(p)}
                          aria-label={`Edit ${p.name}`}
                        >
                          <img
                            src={p.photos[0]?.path}
                            alt={p.name}
                            loading="lazy"
                          />
                          <span
                            className={`stock-badge ${stock(p) === 0 ? "out" : ""}`}
                          >
                            {stock(p) === 0
                              ? "Out of stock"
                              : stock(p) < 10
                                ? "Low stock"
                                : "In stock"}
                          </span>
                          <span className="edit-badge">
                            <FiEdit2 />
                          </span>
                        </button>
                        <div className="product-meta">
                          <span className="product-id">{p.p_id}</span>
                          <div className="product-title">
                            <button onClick={() => setEditor(p)}>
                              {p.name}
                            </button>
                            <strong>{money(p.price)}</strong>
                          </div>
                          <div className="product-bottom">
                            <div className="swatches">
                              {[
                                ...new Map(
                                  p.variants.map((v) => [v.color_id, v]),
                                ).values(),
                              ].map((v) => (
                                <span
                                  key={v.color_id}
                                  style={{ background: v.hex }}
                                  title={v.color}
                                />
                              ))}
                              <span className="size-summary">
                                {[
                                  ...new Set(p.variants.map((v) => v.size)),
                                ].join(" / ")}
                              </span>
                            </div>
                            <span>{stock(p)} units</span>
                          </div>
                          <button
                            className="delete-link"
                            onClick={() => setDeleting(p)}
                          >
                            <FiTrash2 /> Delete
                          </button>
                        </div>
                      </article>
                    ))}
                  </div>
                ) : (
                  <div className="empty-state">
                    <div className="empty-icon">
                      <FiPackage />
                    </div>
                    <h2>
                      {search || filter !== "all"
                        ? "No matching pieces"
                        : "Your collection starts here"}
                    </h2>
                    <p>
                      {search || filter !== "all"
                        ? "Try a different search or stock filter."
                        : "Add your first product, choose its colors, and make room for every size."}
                    </p>
                    <button
                      className="primary"
                      onClick={() =>
                        search || filter !== "all"
                          ? (setSearch(""), setFilter("all"))
                          : setEditor("new")
                      }
                    >
                      {search || filter !== "all"
                        ? "Clear filters"
                        : "Add your first product"}
                      <FiArrowUpRight />
                    </button>
                  </div>
                )}
              </section>
              <footer>
                Thoughtfully organized. Ready for what’s next.
                <span>ARRCHIT SHOP STUDIO</span>
              </footer>
            </>
          ) : (
            <Options sizes={sizes} colors={colors} onChange={refresh} />
          )}
        </div>
      </main>
      {editor && (
        <Editor
          product={editor === "new" ? null : editor}
          sizes={sizes}
          colors={colors}
          onClose={() => setEditor(null)}
          onOptions={refresh}
          onSaved={async () => {
            await refresh();
            setEditor(null);
            setNotice("Product saved to your collection.");
          }}
        />
      )}
      {deleting && (
        <div className="overlay">
          <section
            className="confirm-dialog"
            role="dialog"
            aria-modal="true"
            aria-labelledby="delete-title"
          >
            <h2 id="delete-title">Delete {deleting.name}?</h2>
            <p>
              This removes the product, all variants, and its photos
              permanently.
            </p>
            <div className="form-actions">
              <button
                className="secondary"
                disabled={busy}
                onClick={() => setDeleting(null)}
              >
                Keep product
              </button>
              <button
                className="danger"
                disabled={busy}
                onClick={async () => {
                  setBusy(true);
                  try {
                    await api(`/products/${deleting.id}`, { method: "DELETE" });
                    setDeleting(null);
                    await refresh();
                    setNotice("Product deleted.");
                  } catch (e) {
                    setError((e as Error).message);
                    setDeleting(null);
                  } finally {
                    setBusy(false);
                  }
                }}
              >
                {busy ? "Deleting…" : "Delete product"}
              </button>
            </div>
          </section>
        </div>
      )}
    </div>
  );
}
function Stat({
  label,
  value,
  detail,
  icon,
}: {
  label: string;
  value: string | number;
  detail: string;
  icon: React.ReactNode;
}) {
  return (
    <div className="stat">
      <div className="stat-top">
        {label}
        <span>{icon}</span>
      </div>
      <strong>{value}</strong>
      <small>{detail}</small>
    </div>
  );
}
function Login({
  error: initialError,
  onLogin,
}: {
  error: string;
  onLogin: (a: Admin) => void;
}) {
  const [error, setError] = useState("");
  const [busy, setBusy] = useState(false);
  async function submit(e: FormEvent<HTMLFormElement>) {
    e.preventDefault();
    setBusy(true);
    setError("");
    const form = new FormData(e.currentTarget);
    try {
      const session = await api<{ csrf: string }>("/session");
      csrf = session.csrf;
      const d = await api<{ admin: Admin; csrf: string }>("/login", {
        method: "POST",
        body: JSON.stringify(Object.fromEntries(form)),
      });
      csrf = d.csrf;
      onLogin(d.admin);
    } catch (e) {
      setError((e as Error).message);
    } finally {
      setBusy(false);
    }
  }
  return (
    <div className="login-page">
      <section className="login-story">
        <div className="brand">
          <span className="brand-mark">a.</span>
          <span>
            arrchit<span className="brand-sub">SHOP STUDIO</span>
          </span>
        </div>
        <div>
          <span className="eyebrow">A LITTLE ORDER. A LOT OF STYLE.</span>
          <h1>
            Behind every
            <br />
            great collection,
            <br />
            <em>a good studio.</em>
          </h1>
          <p>
            Your products, colors, and every size in between.
            <br />
            All together in one thoughtful workspace.
          </p>
        </div>
        <span className="story-footer">BUILT FOR YOUR NEXT CHAPTER ↗</span>
      </section>
      <section className="login-form">
        <div className="login-inner">
          <div className="eyebrow">WELCOME TO YOUR WORKSPACE</div>
          <h2>Good to see you.</h2>
          <p>Sign in to take care of your collection.</p>
          <form onSubmit={submit}>
            <label>
              Email address
              <input
                type="email"
                name="email"
                autoComplete="username"
                placeholder="you@yourstore.com"
                required
                maxLength={190}
              />
            </label>
            <label>
              Password
              <input
                type="password"
                name="password"
                autoComplete="current-password"
                placeholder="Enter your password"
                required
                maxLength={1024}
              />
            </label>
            {(error || initialError) && (
              <div className="alert" role="alert">
                {error || initialError}
              </div>
            )}
            <button className="primary" disabled={busy}>
              {busy ? "Signing in…" : "Sign in to studio"}
              <FiArrowUpRight />
            </button>
          </form>
          <p className="login-help">
            Your workspace is private. Ask your store owner
            <br />
            for an administrator account.
          </p>
        </div>
      </section>
    </div>
  );
}
function Options({
  sizes,
  colors,
  onChange,
}: {
  sizes: Size[];
  colors: Color[];
  onChange: () => Promise<void>;
}) {
  const [error, setError] = useState("");
  const [busy, setBusy] = useState(false);
  async function add(e: FormEvent<HTMLFormElement>, kind: string) {
    e.preventDefault();
    const form = e.currentTarget;
    setBusy(true);
    setError("");
    try {
      await api(`/${kind}`, {
        method: "POST",
        body: JSON.stringify(Object.fromEntries(new FormData(form))),
      });
      await onChange();
      form.reset();
    } catch (e) {
      setError((e as Error).message);
    } finally {
      setBusy(false);
    }
  }
  return (
    <div className="options-grid">
      {error && (
        <div className="alert" role="alert">
          {error}
        </div>
      )}
      <section className="panel">
        <span className="panel-icon">
          <FiLayers />
        </span>
        <h2>Every size fits here.</h2>
        <p>Letter sizes, number sizes, or something new.</p>
        <div className="option-chips">
          {sizes.map((s) => (
            <span key={s.id}>{s.label}</span>
          ))}
        </div>
        <form onSubmit={(e) => add(e, "sizes")}>
          <label>
            New size
            <input
              name="label"
              placeholder="e.g. XL, 31, One size"
              required
              maxLength={32}
            />
          </label>
          <button className="primary" disabled={busy}>
            <FiPlus /> Add size
          </button>
        </form>
      </section>
      <section className="panel">
        <span className="panel-icon">
          <FiGrid />
        </span>
        <h2>A color for every mood.</h2>
        <p>Build a palette that grows with your collection.</p>
        <div className="color-options">
          {colors.map((c) => (
            <span key={c.id}>
              <i style={{ background: c.hex }} />
              {c.name}
            </span>
          ))}
        </div>
        <form onSubmit={(e) => add(e, "colors")}>
          <label>
            Color name
            <input
              name="name"
              placeholder="e.g. Washed blue"
              required
              maxLength={60}
            />
          </label>
          <label>
            Swatch
            <input type="color" name="hex" defaultValue="#798979" />
          </label>
          <button className="primary" disabled={busy}>
            <FiPlus /> Add color
          </button>
        </form>
      </section>
    </div>
  );
}
function Editor({
  product,
  sizes,
  colors,
  onClose,
  onSaved,
  onOptions,
}: {
  product: Product | null;
  sizes: Size[];
  colors: Color[];
  onClose: () => void;
  onSaved: () => Promise<void>;
  onOptions: () => Promise<void>;
}) {
  const [variants, setVariants] = useState<Variant[]>(
    product?.variants.map((v) => ({ ...v, quantity: Number(v.quantity) })) || [
      {
        size_id: Number(sizes[0]?.id),
        color_id: Number(colors[0]?.id),
        quantity: 0,
      },
    ],
  );
  const [photos, setPhotos] = useState<DraftPhoto[]>(
    product?.photos.map((p) => ({
      key: `old:${p.id}`,
      url: p.path,
      color_id: p.color_id === null ? null : Number(p.color_id),
    })) || [],
  );
  const [error, setError] = useState("");
  const [busy, setBusy] = useState(false);
  const [option, setOption] = useState("");
  const dialogRef = useRef<HTMLElement>(null);
  useEffect(() => {
    const previous = document.activeElement as HTMLElement | null;
    dialogRef.current?.focus();
    return () => previous?.focus();
  }, []);
  useEffect(() => {
    const previous = document.body.style.overflow;
    document.body.style.overflow = "hidden";
    return () => {
      document.body.style.overflow = previous;
    };
  }, []);
  async function upload(files: FileList | null) {
    if (!files) return;
    if (photos.length + files.length > 12) {
      setError("Use at most 12 photos.");
      return;
    }
    try {
      const next = await Promise.all(
        [...files].map(
          (file) =>
            new Promise<DraftPhoto>((resolve, reject) => {
              if (
                !["image/jpeg", "image/png", "image/webp"].includes(
                  file.type,
                ) ||
                file.size > 5 * 1024 * 1024
              ) {
                reject(new Error("Use JPEG, PNG, or WebP images under 5 MB."));
                return;
              }
              const reader = new FileReader();
              reader.onload = () =>
                resolve({
                  key: crypto.randomUUID(),
                  url: String(reader.result),
                  file,
                  color_id: null,
                });
              reader.onerror = () => reject(new Error("Could not read photo."));
              reader.readAsDataURL(file);
            }),
        ),
      );
      setPhotos((p) => [...p, ...next]);
      setError("");
    } catch (e) {
      setError((e as Error).message);
    }
  }
  function move(index: number, offset: number) {
    setPhotos((p) => {
      const next = [...p];
      [next[index], next[index + offset]] = [next[index + offset], next[index]];
      return next;
    });
  }
  async function save(e: FormEvent<HTMLFormElement>) {
    e.preventDefault();
    setBusy(true);
    setError("");
    const fields = Object.fromEntries(new FormData(e.currentTarget));
    const body = new FormData();
    let index = 0;
    const order = photos.map((p) => {
      if (p.file) {
        body.append("photos[]", p.file);
        return { key: `new:${index++}`, color_id: p.color_id };
      }
      return { key: p.key, color_id: p.color_id };
    });
    body.append(
      "product",
      JSON.stringify({ ...fields, id: product?.id, variants, photos: order }),
    );
    try {
      await api("/products", { method: "POST", body });
      await onSaved();
    } catch (e) {
      setError((e as Error).message);
    } finally {
      setBusy(false);
    }
  }
  async function addOption(e: FormEvent<HTMLFormElement>) {
    e.preventDefault();
    const form = e.currentTarget;
    setBusy(true);
    try {
      await api(`/${option}`, {
        method: "POST",
        body: JSON.stringify(Object.fromEntries(new FormData(form))),
      });
      await onOptions();
      setOption("");
      setError("");
    } catch (e) {
      setError((e as Error).message);
    } finally {
      setBusy(false);
    }
  }
  return (
    <div className="overlay">
      <section
        className="editor"
        ref={dialogRef}
        tabIndex={-1}
        onKeyDown={(event) => {
          if (event.key === "Escape" && !busy) onClose();
          if (event.key !== "Tab") return;
          const controls = [
            ...event.currentTarget.querySelectorAll<HTMLElement>(
              "button:not(:disabled), input:not(:disabled), select:not(:disabled), textarea:not(:disabled)",
            ),
          ];
          const first = controls[0],
            last = controls[controls.length - 1];
          if (
            event.shiftKey &&
            (document.activeElement === first ||
              document.activeElement === event.currentTarget)
          ) {
            event.preventDefault();
            last?.focus();
          } else if (!event.shiftKey && document.activeElement === last) {
            event.preventDefault();
            first?.focus();
          }
        }}
        role="dialog"
        aria-modal="true"
        aria-labelledby="editor-title"
      >
        <header className="editor-header">
          <div>
            <div className="eyebrow">MAKE IT PART OF THE COLLECTION</div>
            <h2 id="editor-title">
              {product ? "Edit product" : "A new addition"}
              <span className="title-dot">.</span>
            </h2>
          </div>
          <button
            className="icon-button"
            disabled={busy}
            onClick={onClose}
            aria-label="Close editor"
          >
            <FiX />
          </button>
        </header>
        <div className="editor-scroll">
          {error && (
            <div className="alert" role="alert">
              {error}
            </div>
          )}
          {option && (
            <form className="inline-option" onSubmit={addOption}>
              <h3>Add {option === "sizes" ? "size" : "color"}</h3>
              <input
                aria-label="New option name"
                required
                name={option === "sizes" ? "label" : "name"}
                maxLength={option === "sizes" ? 32 : 60}
                placeholder={
                  option === "sizes" ? "e.g. XL or 32" : "Color name"
                }
              />
              {option === "colors" && (
                <input
                  type="color"
                  name="hex"
                  aria-label="Color swatch"
                  defaultValue="#798979"
                />
              )}
              <button className="primary" disabled={busy}>
                Add
              </button>
              <button
                type="button"
                className="secondary"
                onClick={() => setOption("")}
              >
                Cancel
              </button>
            </form>
          )}
          <form id="product-form" onSubmit={save}>
            <div className="section-title">
              <span>01</span>
              <h3>The essentials</h3>
            </div>
            <div className="form-grid">
              <label>
                Product name
                <input
                  name="name"
                  defaultValue={product?.name}
                  placeholder="e.g. Everyday linen shirt"
                  required
                  maxLength={160}
                />
              </label>
              <label>
                Product ID
                <input
                  name="p_id"
                  defaultValue={product?.p_id}
                  placeholder="e.g. SH-001"
                  required
                  maxLength={64}
                />
                <small>One ID for the same product, across all colors.</small>
              </label>
              <label>
                Price (USD)
                <input
                  type="number"
                  min="0"
                  max="9999999999.99"
                  step="0.01"
                  name="price"
                  defaultValue={product?.price}
                  placeholder="0.00"
                  required
                />
              </label>
              <label>
                Description <span className="muted">(optional)</span>
                <textarea
                  name="description"
                  defaultValue={product?.description}
                  placeholder="Fabric, fit, and the little details…"
                  maxLength={5000}
                />
              </label>
            </div>
            <div className="section-title">
              <span>02</span>
              <h3>Colors, sizes & stock</h3>
            </div>
            <p className="section-help">
              Add a row for every color and size combination. Quantities are
              tracked separately.
            </p>
            <div className="variant-head">
              <span>Color</span>
              <span>Size</span>
              <span>Quantity</span>
              <span />
            </div>
            {variants.map((v, i) => (
              <div className="variant-row" key={i}>
                <select
                  aria-label={`Color for variant ${i + 1}`}
                  required
                  value={v.color_id || ""}
                  onChange={(e) =>
                    setVariants((rows) =>
                      rows.map((r, j) =>
                        j === i
                          ? { ...r, color_id: Number(e.target.value) }
                          : r,
                      ),
                    )
                  }
                >
                  <option value="" disabled>
                    Select color
                  </option>
                  {colors.map((c) => (
                    <option key={c.id} value={c.id}>
                      {c.name}
                    </option>
                  ))}
                </select>
                <select
                  aria-label={`Size for variant ${i + 1}`}
                  required
                  value={v.size_id || ""}
                  onChange={(e) =>
                    setVariants((rows) =>
                      rows.map((r, j) =>
                        j === i ? { ...r, size_id: Number(e.target.value) } : r,
                      ),
                    )
                  }
                >
                  <option value="" disabled>
                    Select size
                  </option>
                  {sizes.map((s) => (
                    <option key={s.id} value={s.id}>
                      {s.label}
                    </option>
                  ))}
                </select>
                <input
                  aria-label={`Quantity for variant ${i + 1}`}
                  type="number"
                  min="0"
                  max="1000000"
                  step="1"
                  required
                  value={Number.isNaN(v.quantity) ? "" : v.quantity}
                  onChange={(e) =>
                    setVariants((rows) =>
                      rows.map((r, j) =>
                        j === i
                          ? {
                              ...r,
                              quantity:
                                e.target.value === ""
                                  ? NaN
                                  : Number(e.target.value),
                            }
                          : r,
                      ),
                    )
                  }
                />
                <button
                  type="button"
                  className="icon-button"
                  disabled={variants.length === 1}
                  onClick={() =>
                    setVariants((rows) => rows.filter((_, j) => j !== i))
                  }
                  aria-label={`Remove variant ${i + 1}`}
                >
                  <FiTrash2 />
                </button>
              </div>
            ))}
            <div className="variant-actions">
              <button
                type="button"
                className="secondary"
                onClick={() =>
                  setVariants((rows) => [
                    ...rows,
                    {
                      color_id: Number(colors[0]?.id),
                      size_id: Number(sizes[0]?.id),
                      quantity: 0,
                    },
                  ])
                }
              >
                <FiPlus /> Add combination
              </button>
              <button
                type="button"
                className="text-button"
                onClick={() => setOption("sizes")}
              >
                + New size
              </button>
              <button
                type="button"
                className="text-button"
                onClick={() => setOption("colors")}
              >
                + New color
              </button>
            </div>
            <div className="section-title">
              <span>03</span>
              <h3>A picture of your product</h3>
            </div>
            <p className="section-help">
              The first image is your cover. Reorder photos and optionally link
              each one to a color.
            </p>
            <label className="upload-zone">
              <FiImage />
              <strong>Choose product photos</strong>
              <span>
                JPEG, PNG or WebP · up to 5 MB each · 12 photos maximum
              </span>
              <input
                type="file"
                accept="image/jpeg,image/png,image/webp"
                multiple
                onChange={(e) => {
                  void upload(e.target.files);
                  e.target.value = "";
                }}
              />
            </label>
            <div className="photo-list">
              {photos.map((p, i) => (
                <div className="photo-row" key={p.key}>
                  <img src={p.url} alt={`Product photo ${i + 1}`} />
                  <div>
                    <strong>
                      {i === 0 ? "Cover image" : `Photo ${i + 1}`}
                    </strong>
                    <select
                      aria-label={`Color for photo ${i + 1}`}
                      value={p.color_id ?? ""}
                      onChange={(e) =>
                        setPhotos((rows) =>
                          rows.map((r) =>
                            r.key === p.key
                              ? {
                                  ...r,
                                  color_id: e.target.value
                                    ? Number(e.target.value)
                                    : null,
                                }
                              : r,
                          ),
                        )
                      }
                    >
                      <option value="">All colors</option>
                      {colors
                        .filter((c) =>
                          variants.some(
                            (v) => Number(v.color_id) === Number(c.id),
                          ),
                        )
                        .map((c) => (
                          <option key={c.id} value={c.id}>
                            {c.name}
                          </option>
                        ))}
                    </select>
                  </div>
                  <button
                    type="button"
                    className="icon-button"
                    disabled={i === 0}
                    onClick={() => move(i, -1)}
                    aria-label={`Move photo ${i + 1} up`}
                  >
                    <FiArrowUp />
                  </button>
                  <button
                    type="button"
                    className="icon-button"
                    disabled={i === photos.length - 1}
                    onClick={() => move(i, 1)}
                    aria-label={`Move photo ${i + 1} down`}
                  >
                    <FiArrowDown />
                  </button>
                  <button
                    type="button"
                    className="icon-button"
                    onClick={() =>
                      setPhotos((rows) => rows.filter((r) => r.key !== p.key))
                    }
                    aria-label={`Remove photo ${i + 1}`}
                  >
                    <FiTrash2 />
                  </button>
                </div>
              ))}
            </div>
          </form>
        </div>
        <div className="editor-footer">
          <span>{photos.length} / 12 photos</span>
          <button className="secondary" disabled={busy} onClick={onClose}>
            Cancel
          </button>
          <button
            className="primary"
            form="product-form"
            disabled={busy || photos.length === 0}
          >
            {busy ? "Saving…" : "Save product"}
            <FiCheck />
          </button>
        </div>
      </section>
    </div>
  );
}
