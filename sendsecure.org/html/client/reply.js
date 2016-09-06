var quill = null;
document.addEventListener('DOMContentLoaded', function() {
    if (_platform == 'desktop') {
        var toolbarOptions = [
            [{ 'font': [] }],
            [{ 'size': ['small', false, 'large', 'huge'] }],	// custom dropdown
            ['bold', 'italic', 'underline', 'strike'],				// toggled buttons
            [{ 'color': [] }, { 'background': [] }],					// dropdown with defaults from theme
            [{ 'list': 'ordered'}, { 'list': 'bullet' }],
            [{ 'align': [] }],
            ['blockquote'],
            [{ 'indent': '-1'}, { 'indent': '+1' }],					// outdent/indent
            ['link'],
            ['clean']														// remove formatting button
        ];
    }
    if (_platform == 'mobile') {
        var toolbarOptions = [
            [{ 'size': ['small', false, 'large', 'huge'] }],	// custom dropdown
            ['bold', 'italic', 'underline'],				// toggled buttons
            [{ 'color': [] }],					// dropdown with defaults from theme
            ['clean']														// remove formatting button
        ];
    }

	quill = new Quill('#editor', {
		modules: {
			'toolbar': toolbarOptions
		},
		theme: 'snow'
	});

	quill.focus();
	
	quill.on('text-change', function() {
		document.getElementById('editor-print').innerHTML = document.getElementById('editor').firstChild.innerHTML
	})
	
} , false);

document.addEventListener('DOMContentLoaded', function() {
	document.getElementById('remove-attach').style.display = 'none';
	document.getElementById('attachments').style.display = 'none';
} , false);

function addAttach(){
	document.getElementById('remove-attach').style.display = '';
	document.getElementById('attachments').style.display = '';
	node = document.createElement('input');
	node.type = 'file';
	node.name = Date.now();
	node.style.visibility = 'hidden';
	node.style.width = '0px';
	node.style.height = '0px';
	node.click();
	node.addEventListener("change", function() { changedAttach(node) });
	document.getElementById('attach-container').appendChild(node);
}

function removeAttach(){
	document.getElementById('attachments').style.display = 'none';
	document.getElementById('remove-attach').style.display = 'none';
	var attachments = document.getElementById('attach-names'); // we remove the text
	while (attachments.firstChild) {
		attachments.removeChild(attachments.firstChild);
	}
	var attachments = document.getElementById('attach-container'); // we remove the input nodes
	while (attachments.firstChild) {
		attachments.removeChild(attachments.firstChild);
	}
}

function send(){
	node = document.createElement('input');
	node.type = 'hidden';
	node.name = 'message';
	node.value = htmlspecialchars(document.getElementById('editor').firstChild.innerHTML);
	document.forms[0].appendChild(node);
	document.forms[0].submit();
}

function htmlspecialchars(inputText) {
	var map = {
		'&': '&amp;',
		'<': '&lt;',
		'>': '&gt;',
		'"': '&quot;',
		"'": '&#039;'
	};
	return inputText.replace(/[&<>"']/g, function(m) { return map[m]; });
}

// a callback when the file attachment is selected
function changedAttach(node){
	// convert filepath to filename
	file = node.value.split(/(\\|\/)/g).pop();
	
	div = document.createElement('div');
	div.style.whiteSpace = 'pre';
	div.innerText = file;
	document.getElementById('attach-names').appendChild(div);
}

document.addEventListener('DOMContentLoaded', fixDate, false);
function fixDate(){
	d = document.getElementById('now');
	d.innerText = moment(Date.parse(d.innerText)).format('ddd, MMM DD, YYYY [at] h:mm A');
	
	var div = document.createElement('div');
	div.innerHTML = document.getElementById('message').value;
	d = div.querySelector("#oldMsg").querySelector("#then");
	d.innerText = moment(Date.parse(d.innerText)).format('ddd, MMM DD, YYYY [at] h:mm A');
	document.getElementById('message').value = div.innerHTML;
	quill.pasteHTML(0, document.getElementById('message').value);
}
