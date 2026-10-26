import {BrowserRouter,Route,Routes,Navigate} from 'react-router-dom'
import Main from './../pages/main/Main'
import Home from './../pages/home/Home'
import Stock from './../pages/products/Stock'
import Instock from './../pages/products/Instock'
import History from './../pages/history/History'
import Addnew from './../pages/addnew/Addnew'
import Search from './../pages/search/Search'
import Profile from './../pages/menu/Profile'
import List from './../pages/products/List'
import Login from './../auth/Login'
import Register from './../auth/Register'
import {useContext} from 'react'
import {AuthContext} from './../context/AuthContext'
export default function Router(){
	const {auth}=useContext(AuthContext);
	return (
	<BrowserRouter>
	<Routes>
	<Route path="/" element={auth?<Main />:<Navigate to="login"/>} >
	<Route path="/" element={<Navigate to="/home" />} />
	<Route path="/home" element={<Home />} />
	<Route path="/products/list" element={<List />} />
	<Route path="/products/stock" element={<Stock />} />
	<Route path="/products/instock/:data" element={<Instock />} />
	<Route path="/history" element={<History />} />
	<Route path="/addnew" element={<Addnew />} />
	<Route path="/search" element={<Search />} />
	<Route path="/profile" element={<Profile />} />
	</Route>
	<Route path="/login" element={auth?<Navigate to="/" />: <Login />}/>
	<Route path="/register" element={auth?<Navigate to="/" />:<Register />} />
	</Routes>
	</BrowserRouter>
	)
}