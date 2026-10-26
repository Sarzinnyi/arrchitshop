import {useParams} from 'react-router-dom'
import {useState,useEffect} from 'react'
export default function Instock(){
	const {data}=useParams();
	const [p_id,color]=data.split('_');
	const [pdetail,Pdetail]=useState();
	const load=async()=>{
		if(!p_id || !color){
			return ;
		}
		const fd=new FormData();
		fd.append('p_id',p_id);
		fd.append('color',color);
		const res=await fetch('http://api.arrchit.com/products/detail',{
			method:"POST",
			credentials:"include",
			body:fd,
		});
		const data=await res.json();
	}
	useEffect(()=>{load();},[]);
	return (
	<div className="instock">
	<h3>Instock{p_id}{color}</h3>
	<section className="instockbox">
	<section className="instockimg">
	
	</section>
	<section className="instockdata">
	
	</section>
	</section>
	<section className="instockinput">
	
	</section>
	</div>
	)
}