document.getElementById('btn__show').addEventListener('click', (e) => {
    document.querySelectorAll('.checkbox--show').forEach(item => {
        if(item.checked == true) {
            item.checked = false;
        } else {
            item.checked = true;
        }
    });
});
document.getElementById('btn__edit').addEventListener('click', (e) => {
    document.querySelectorAll('.checkbox--edit').forEach(item => {
        if(item.checked == true) {
            item.checked = false;
        } else {
            item.checked = true;
        }
    });
});
document.getElementById('btn__remove').addEventListener('click', (e) => {
    document.querySelectorAll('.checkbox--remove').forEach(item => {
        if(item.checked == true) {
            item.checked = false;
        } else {
            item.checked = true;
        }
    });
});
document.querySelectorAll('.btn--trigger').forEach(item => {
    item.addEventListener('click', e => {
        document.querySelectorAll('.checkbox--show').forEach(showButton => {
            if(showButton.checked == true) {
                if(document.getElementById('moduleArguments[privileges][edit][' + showButton.dataset.id + ']-' + showButton.dataset.id).hasAttribute('onclick') && document.getElementById('moduleArguments[privileges][remove][' + showButton.dataset.id + ']-' + showButton.dataset.id).hasAttribute('onclick')) {
                    document.getElementById('moduleArguments[privileges][edit][' + showButton.dataset.id + ']-' + showButton.dataset.id).removeAttribute('onclick');
                    document.getElementById('moduleArguments[privileges][remove][' + showButton.dataset.id + ']-' + showButton.dataset.id).removeAttribute('onclick');
                }
                document.getElementById('moduleArguments[privileges][show][' + showButton.dataset.parent + ']-' + showButton.dataset.parent).checked = true;
            } else {
                document.getElementById('moduleArguments[privileges][edit][' + showButton.dataset.id + ']-' + showButton.dataset.id).checked = false;
                document.getElementById('moduleArguments[privileges][remove][' + showButton.dataset.id + ']-' + showButton.dataset.id).checked = false;
                document.getElementById('moduleArguments[privileges][edit][' + showButton.dataset.id + ']-' + showButton.dataset.id).setAttribute('onclick', 'this.checked=false;');
                document.getElementById('moduleArguments[privileges][remove][' + showButton.dataset.id + ']-' + showButton.dataset.id).setAttribute('onclick', 'this.checked=false;');
            }
        });
    });
});
