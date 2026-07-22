
function WHCreateCookie(name, value, days) {
    var date = new Date();
    date.setTime(date.getTime() + (days*24*60*60*1000));
    var expires = "; expires=" + date.toGMTString();
	document.cookie = name+"="+value+expires+"; path=/";
}
function WHReadCookie(name) {
	var nameEQ = name + "=";
	var ca = document.cookie.split(';');
	for(var i=0; i < ca.length; i++) {
		var c = ca[i];
		while (c.charAt(0) == ' ') c = c.substring(1, c.length);
		if (c.indexOf(nameEQ) == 0) return c.substring(nameEQ.length, c.length);
	}
	return null;
}

window.addEventListener('load', WHCheckCookies);

function WHCheckCookies() {
    var cookiesContainer = document.getElementById('cookies-message-container');
    var cookiesMessage = document.getElementById('cookies-message');
    if(!cookiesContainer || !cookiesMessage) {
        return;
    }
    if(WHReadCookie('cookies_accepted') != 'T') {
		cookiesContainer.style.display = "block";
		cookiesMessage.style.display = "flex";
	}
}

function WHCloseCookiesWindow() {
    WHCreateCookie('cookies_accepted', 'T', 365);
    var cookiesContainer = document.getElementById('cookies-message-container');
    if(cookiesContainer) {
        cookiesContainer.style.display = 'none';
    }
}
