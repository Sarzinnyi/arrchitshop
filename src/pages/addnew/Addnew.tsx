import './Addnew.css'
import {useState,useEffect} from 'react'
import {useAuth} from './../../context/AuthContext'
export default function Addnew(){
	const {api_url}=useAuth();
	const [c,C]=useState('');
	const [n,N]=useState('');
	const [cr,Cr]=useState('');
	const [p,P]=useState('');
	const [pho,Pho]=useState();
	const [sl,Sl]=useState();
	const [sn,Sn]=useState();
	const [preview,Preview]=useState();
	const [sizecategory,setSizecategory]=useState('');
	const [postsizesarray,setPostsizesarray]=useState([]);
	const [selectsizes,setSelectsizes]=useState([]);
	const [selectcompelete,setSelectcompelete]=useState(false);
	const hdimg=(e)=>{
		let file=e.target.files[0];
		if(file){
		Pho(file);
        Preview(URL.createObjectURL(file));		
		}
	}
	
	const load=async()=>{
		const res=await fetch(`${api_url}/admin/products/sizes`,{credentials:"include"});
		if (res.ok) {
    const data = await res.json();
   
	if(data.success){
			Sl(data.sizes_letter);
			Sn(data.sizes_number);
		}
    } else {
    console.log("Request failed:", res.status);
     }
		
	}
	useEffect(()=>{load();},[]);
	
	const uploaddata=async()=>{
		if(!c || !n || !cr || !p || !pho || !qs){
			return ;
		}
		const fd=new FormData();
		fd.append('category',c);
		fd.append('name',n);
		fd.append('color',cr);
		fd.append('price',p);
		fd.append('photo',pho);
		
		const res=await fetch('http://api.arrchit.com/products/addnew',{
			method:"POST",
			credentials:"include",
			body:fd,
		});
		const data=await res.json();
		if(data.success){
			
		}
	}
	
	const handleSizeChange = (size, qty) => {
    setPostsizesarray(prev => {
        const index = prev.findIndex(item => item.size === size);

        if (index !== -1) {
            // Size already exists → update it
            const newArray = [...prev];
            newArray[index] = {
                ...newArray[index],
                qty: qty
            };

            return newArray;
        }

        // Size doesn't exist → add it
        return [
            ...prev,
            {
                size: size,
                qty: qty
            }
        ];
    });
};

const handleselectbox=(size)=>{
	setSelectsizes(prev =>{
		if(prev.includes(size)){
			const filter=prev.filter(i => i !== size);
			return filter;
		}else{
			return [...prev,size];
		}
	})
}
console.log('sarray',postsizesarray);
console.log('select',selectsizes);
	return (
	<div className="addnew">
	<p className="addnewhead">Addnew</p>
	<div className="wrap">
	<section className="addnewleft">
	<section className="addnewimg">
	<label htmlFor="animg"><img src={preview || "/favicon.svg"} alt="upload"  style={{width:"150px",height:"150px",borderRadius:"50%",border:"1px solid gray",objectFit:"cover"}}/></label>
	<input type="file" accept="image/*" id="animg"  style={{display:"none"}} onChange={(e)=>hdimg(e)}/>
	</section>
	<section className="addnewbox">
	<input type="text" placeholder="category"  onChange={(e)=>C(e.target.value)}/><br />
	<input type="text" placeholder="name"  onChange={(e)=>N(e.target.value)}/><br />
	<input type="text" placeholder="color"  onChange={(e)=>Cr(e.target.value)}/><br />
	<input type="text" placeholder="price"  onChange={(e)=>P(e.target.value)}/><br />
	
	<button  onClick={()=>uploaddata()}>Upload</button>
	</section>
	</section>
	<section className="addnewright">
	<section className="sizechoosebox" style={{display:'flex'}}>
	<p style={{fontWeight:'bold'}}>Choose Size Category With Letter or Number ?</p>
      <select onChange={(e)=>{
		  setSizecategory(e.target.value);
	  setSelectsizes([]);
	  setSelectcompelete(false);
	  }}>
	<option value=''>Choose ...</option>
	<option value='letter'>Letter</option>
	<option value='number'>Number</option>
	</select>
	</section>
	<section className="sizeqty">
	<p style={{fontWeight:'bold',fontSize:'1.5rem',marginTop:'3vh'}}>Sizes and Qty</p>
	
		{sizecategory == 'letter' &&  !selectcompelete&& <section className="selectsizesbox">
		<section className="sizesboxwrap">
			{
				sl?.map((i,k)=>(
				<section key={k} className="sizeitem">
				<input type="checkbox"  onChange={()=>handleselectbox(i.size)}/><p>{i.size}</p>
				</section>
				))
			}
			</section>
			<button onClick={()=>setSelectcompelete(true)}>Compelete</button>
		</section>
		}
		
		{sizecategory == 'number' &&  !selectcompelete&& <section className="selectsizesbox">
		<section className="sizesboxwrap">
			{
				sn?.map((i,k)=>(
				<section key={k} className="sizeitem">
				<input type="checkbox"  onChange={()=>handleselectbox(i.size)}/><p>{i.size}</p>
				</section>
				))
			}
			</section>
			<button onClick={()=>setSelectcompelete(true)}>Compelete</button>
		</section>
		}
		
		{selectcompelete&& <section className="addnewsizeletter">
		<h3>Addnewsizeletter</h3>
		<button onClick={()=>{
		setSelectcompelete(false);
		setSelectsizes([]);
		}}>Reselect Sizes</button>
		{
			selectsizes?.map((i,k)=>(
			<section key={k} className="addnewinput" >
			<p className="addnewinputleft">size-{i}</p><input type="number"   placeholder="quantity" onChange={(e)=>handleSizeChange(i,e.target.value)}/>
			</section>
			))
		}
		</section>}
		
			
		
			
	</section>
	</section>
	</div>
	</div>
	)
}