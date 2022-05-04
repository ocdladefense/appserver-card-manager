
let deleteForms = document.getElementsByClassName("delete-form");

console.log(deleteForms);

for(let i = 0; i < deleteForms.length; i++) {

    deleteForms[i].onsubmit = function(e){

        if(!confirm("Are you sure you want to delete this payment method?")){e.preventDefault()}
    };
}