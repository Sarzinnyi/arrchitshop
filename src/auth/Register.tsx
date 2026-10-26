import './Register.css'
import {useNavigate} from 'react-router-dom'
export default function Register(){
	const navigate=useNavigate();
	return (
	<div className="register">
	<section className="tologin">
	<button onClick={()=>navigate('/login')}>Login</button>
	</section>
	<section className="registerbox">
	<h3>Register</h3>
	<input type="text" placeholder="FIRST NAME" className="name"/><input type="text" placeholder="LAST NAME"  className="name"/><br />
	<input type="email" placeholder="example@gmail.com" /><br />
	<input type="phone" placeholder="09.........." /><br />
	<input type="password" placeholder="password" /><br/>
	<input type="password" placeholder="reenter password" /><br />
	<button>Sign Up</button>
	</section>
	
	
	</div>
	)
}