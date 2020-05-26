$(document).ready(function(){
	let data = []; 
	$(".list-group li").click(function() {
		let selectData = $(this).text(); 
		 $(".list-group-item").addClass("success");
		data.push(selectData); 

	});


	let url = $("#crawlerID").val(); 


	function isValidData(arrayElement) {

		if (arrayElement.length > 2) {
			return  false; 
		} else if(arrayElement[0] === arrayElement[1]) {
			return false; 
		} else if (arrayElement[0] != arrayElement[1] && arrayElement.length === 2) {
			return true; 
		} else if (arrayElement.length === 0 || arrayElement.length === 1){
			return false; 
		} else {
			return  true; 
		}	
	}

	$( "#target" ).click(function() {
  		//console.log(isValidData(data));
  		if(isValidData(data)){
  			$.ajax({
  				method: "POST",
  				url: url,
  				data: {"data": data}, 
  				success: function(response){
  					console.log(response); 
  				}, 
  				error: function(response){
  					console.log(response); 
  				}
  			})
  		}
	});
})