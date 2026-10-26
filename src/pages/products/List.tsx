import './List.css'
import {useState,useEffect} from 'react'
import {useNavigate} from 'react-router-dom'
export default function List(){
	const [pl,Pl]=useState([]);
	const navigate=useNavigate();
	const loading=async()=>{
		const res=await fetch('http://api.arrchit.com/products/list',{credentials:"include"});
		const data=await res.json();
		if(data.success){
			Pl(data.productslist);
		}
	}
	useEffect(()=>{loading();},[]);
	return (
	<div className="list">
	<h4>Products List</h4>
	<section className="listtable">
	<table>
	<thead>
	<tr>
	<th>No</th>
	<th>Category</th>
	<th>Name</th>
	<th>Color</th>
	<th>Price</th>
	<th>Photo</th>
	<th>Options</th>
	<th>Date</th>
	</tr>
	</thead>
	<tbody>
	{
		pl?.map((i,k)=>{
			return (
			<tr key={k}>
			<td>{k+1}</td>
			<td>{i.category}</td>
			<td>{i.name}</td>
			<td>{i.color}</td>
			<td>{i.price}</td>
			<td><img src={`http://api.arrchit.com/photo/${i.photo}`} /></td>
			<td><button onClick={()=>navigate(`/products/instock/${i.p_id}_${i.color}`)}>Instock</button><button>Outstock</button></td>
			<td>{i.created_at}</td>
			</tr>
			)
		})
	}
	</tbody>
	</table>
	</section>
	</div>
	)
}