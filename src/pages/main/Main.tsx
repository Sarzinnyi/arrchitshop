import {Outlet,Link,useNavigate} from 'react-router-dom'
import {useState} from 'react'
import './Main.css'
import {useContext} from 'react'
import {AuthContext} from './../../context/AuthContext'
export default function Main(){
	const {logout}=useContext(AuthContext);
	const navigate=useNavigate();
	const [active,setActive]=useState<string | ''>('');
	return (
	<div className="main">
	<section className="mainheader">
	<p>ArrChit</p>
	<nav>
	<section onClick={()=>{navigate('home');setActive('home');}}className={`mainlink ${active == 'home' ? 'active' : '' }`}>Home</section>
	<section className="mainlink  dropdown">
	<section className={`dropshow  ${active == 'products' ? 'active' : '' }`}>Products</section>
	<section className="dropdownitems">
	<section className="dropitem" onClick={()=>{navigate('/products/list');setActive('products');}}>Products List</section>
	<section className="dropitem" onClick={()=>{navigate('/products/stock');setActive('products');}}>Products Stock</section>
	</section>
	</section>
	<section onClick={()=>{navigate('history');setActive('history');}}className={`mainlink ${active == 'history' ? 'active' : '' }`}>History</section>
	<section onClick={()=>{navigate('addnew');setActive('addnew');}}className={`mainlink ${active == 'addnew' ? 'active' : '' }`}>Addnew</section>
	<section onClick={()=>{navigate('search');setActive('search');}}className={`mainlink ${active == 'search' ? 'active' : '' }`}>Search</section>
	<section className="mainlink  dropdown">
	<section className={`dropshow  ${active == 'menu' ? 'active' : '' }`}>Menu</section>
	<section className="dropdownitems">
	<section className="dropitem" onClick={()=>{navigate('profile');setActive('menu');}}>Profile</section>
	<section className="dropitem" onClick={()=>{logout();setActive('menu');}}>Logout</section>
	</section>
	</section>
	</nav>
	</section>
	<section className="mainoutlet">
	<Outlet />
	</section>
	</div>
	)
}