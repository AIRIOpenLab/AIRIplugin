function cipSelectFunction() {
  var e = document.getElementById("cip_select");
  var strOption = e.options[e.selectedIndex].value;
  var strDesc = e.options[e.selectedIndex].innerText;

  if(console) {
	  console.log(strOption + ' ' + strDesc)
  }
  
  //var x = document.getElementById("cip_div");
  //x.innerHTML = "Descrizione: " + strDesc + "<br/>Codice CIP 2010: " + strOption;
  
  var h = document.getElementById("cip2010_desc");
  h.value = strDesc;
} 

function matchCustom(params, data) {
  // If there are no search terms, return all of the data
  if (jQuery.trim(params.term) === '') {
    return null;
  }

  // Skip if there is no 'children' property
  if (typeof data.children === 'undefined') {
    return null;
  }

  // `data.children` contains the actual options that we are matching against
  var filteredChildren = [];
  jQuery.each(data.children, function (idx, child) {
    if (child.text.toUpperCase().indexOf(params.term.toUpperCase()) > -1) {
      filteredChildren.push(child);
    }
  });

  // If we matched any of the group's children, then set the matched children on the group
  // and return the group object
  if (filteredChildren.length) {
    var modifiedData = jQuery.extend({}, data, true);
    modifiedData.children = filteredChildren;

    // You can return modified objects from here
    // This includes matching the `children` how you want in nested data sets
    return modifiedData;
  }

  // Return `null` if the term should not be displayed
  return null;
}		
	
jQuery(document).ready(function($) {
 $('.js-example-basic-single').select2(
 	{ data: cips2010_data,
 	  placeholder: "Seleziona un campo di ricerca, una specializzazione o un'occupazione",
	  allowClear: true,
	  matcher: matchCustom,
	  language: 'it',
	  minimumInputLength: 5,
	  maximumInputLength: 32
 	});
});