import {createContext} from 'react'
import {useState,useEffect,useContext} from 'react'
export const AuthContext=createContext();
export const AuthProvider=({children})=>{
	interface aa{
		[key:any] : any;
	}
	const api_url='http://api.arrchit.com';
	const [auth,Auth]=useState(null);
	const loadingauth=async()=>{
		const res=await fetch("http://api.arrchit.com/admin/admin/info",{credentials:"include"});
		const data=await res.json();
		if(data.success){
			Auth(data.admin);
		}
	};
	
	useEffect(()=>{loadingauth();},[]);
	
	const logout=async()=>{
		const res=await fetch('http://api.arrchit.com/admin/admin/logout',{credentials:"include"});
		const data=await res.json();
		if(data.success){
			loadingauth();
			Auth(null);
		}
	}
	return (
	<AuthContext.Provider value={{auth,logout,loadingauth,api_url}}>
	{children}
	</AuthContext.Provider>
	)
}
export const useAuth=()=>{
	const context=useContext(AuthContext);
	if(!context){
		 throw new Error(
      "useAuth must be used inside AuthProvider"
    );
	}
	return context;
}