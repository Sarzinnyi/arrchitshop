import {useNavigate} from 'react-router-dom'
import {useState,useEffect,useContext} from 'react'
import './Login.css'
import {AuthContext} from './../context/AuthContext'
export default function Login(){
	const {loadingauth}=useContext(AuthContext);
	const navigate=useNavigate();
	const [e,E]=useState('');
	const [p,P]=useState('');
	const [step,Step]=useState(1);
	async function hdlg(){
		if(!e || !p){
			return ;
		}
		const fd=new FormData();
		fd.append('email',e);
		fd.append('password',p);
		const res=await fetch('http://api.arrchit.com/admin/admin/login',{
			method:"POST",
			credentials:"include",
			body:fd,
		});
		const data=await res.json();
		if(res.status == 429){
			Step(2);
		}
		if(data.success){
			loadingauth();
		}
	}
	return (
	<>
	{step == 1 && <Loginpage E={E} P={P} hdlg={hdlg}/>}
		{step == 2 && <Verifyaccount e={e} Step={Step}/>}
			{
				step == 3 && <Changepassword Step={Step}/>
			}
	</>
	)
}

function Loginpage({E,P,hdlg}){
	return (
	<div className="login">
	
	<section className="loginbox">
	<h3>Login</h3>
	<section className="loginbox">
	<input type="email" placeholder="example@gmail.com" onChange={(e)=>E(e.target.value)}/><br />
	<input type="password" placeholder="password" onChange={(e)=>P(e.target.value)}/><br />
	<button onClick={()=>hdlg()}>Login</button>
	</section>
	</section>
	<section className="toregister">
	<button onClick={()=>navigate('/register')}>Sign up</button>
	</section>
	</div>
	)
}

function Verifyaccount({e,Step}){
	const [otp,setOtp]=useState(null);
	const reqotp=async()=>{
		const fd=new FormData();
		fd.append('email',e);
		const res=await fetch('http://api.arrchit.com/admin/admin/accountverifyreqotp',{
			method:"POST",
			credentials:"include",
			body:fd,
		});
		const data=await res.json();
	}
	useEffect(()=>{reqotp();},[]);
const sentotp=async()=>{
	const fd=new FormData();
	fd.append('email',e);
	fd.append('otp',otp);
	const res=await fetch('http://api.arrchit.com/admin/admin/accountverifingotp',{
		method:"POST",
		credentials:"include",
		body:fd,
	});
	const data=await res.json();
	if(data.success){
		Step(3);
	}
}
	return (
	<div className="verifyaccount">
	<h3>Verify Account</h3>
	<input type="text" placeholder="otp"  onChange={(e)=>setOtp(e.target.value)}/><br />
	<span>Sent otp to {e}</span><br />
	<button onClick={()=>reqotp()}>Resent</button><br />
	<button onClick={()=>sentotp()}>Verify</button>
	</div>
	)
}

function Changepassword({Step}){
	const [np,Np]=useState();
	const [rp,Rp]=useState();
	const changepw=async()=>{
		const fd=new FormData();
		fd.append('password',np);
		fd.append('reenterpassword',rp);
		const res=await fetch('http://api.arrchit.com/admin/admin/accountchangepassword',{
			method:"POST",
			credentials:"include",
			body:fd,
		});
		const data=await res.json();
		if(data.success){
			alert('Success Change Password');
			await new Promise(resolve => setTimeout(resolve, 2000));
			Step(1);
		}
	}
	return (
	<div className="changepassword">
	<h3>Change Password</h3>
	<input type="password" placeholder="password" onChange={(e)=>Np(e.target.value)}/><br />
	<input type="password" placeholder="re-enter password" onChange={(e)=>Rp(e.target.value)}/><br />
	<button onClick={()=>changepw()}>Confirm</button>
	</div>
	)
}