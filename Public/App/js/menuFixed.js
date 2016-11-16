function menuFixed(id){ 
	var obj = document.getElementById(id); 
	var _getHeight = obj.offsetTop; 
	window.onscroll = function(){ 
		changePos(id,_getHeight); 
	} 
} 
function changePos(id,height){ 
	var obj = document.getElementById(id); 
	var scrollTop = document.documentElement.scrollTop || document.body.scrollTop; 
	var tags = obj.getElementsByTagName("*");
	if(scrollTop < height){ 
		obj.style.position = 'relative';
		document.getElementById('index-plist').style.marginTop="0";
		//obj.style.marginTop='0';
//		for(var i = 0; i < tags.length; i++){
//		    if (!!tags[i].id && tags[i].id == 'ui-imgs'){
//		        tags[i].style.display = 'block';
//		    }else if(!!tags[i].id && tags[i].id == 'ui-tts'){
//		    	tags[i].style.padding='0';
//		    }else if(!!tags[i].id && tags[i].id == 'ui-li'){
//		    	tags[i].style.padding='10px 0';
//		    }
//		}
	}else{ 
		obj.style.position = 'fixed'; 
		obj.style.top=0;
		/*document.getElementById('index-plist').style.marginTop="80px";*/
		//obj.style.marginTop='50px';
//		for(var i = 0; i < tags.length; i++){
//		    if (!!tags[i].id && tags[i].id == 'ui-imgs'){
//		         tags[i].style.display = 'none';
//		    }else if(!!tags[i].id && tags[i].id == 'ui-tts'){
//		    	tags[i].style.padding='0 10px';
//		    }else if(!!tags[i].id && tags[i].id == 'ui-li'){
//		    	tags[i].style.padding='5px 0';
//		    }
//		}
	} 
} 
