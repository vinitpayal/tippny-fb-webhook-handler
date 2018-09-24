// setup facebook sdk on page load so that checkbox plugin can initiated
$(document).ready(function() {
    $.ajaxSetup({ cache: true });
    $.getScript('https://connect.facebook.net/en_US/sdk.js', function(){
        FB.init({
            appId: '293870131126505',
            autoLogAppEvents : true,
            xfbml            : true,
            version          : 'v3.1'
        });
        (function(d, s, id){
            var js, fjs = d.getElementsByTagName(s)[0];
            console.log(js, fjs);
            if (d.getElementById(id)) {return;}
            js = d.createElement(s); js.id = id;
            js.src = "https://connect.facebook.net/en_US/sdk.js";
            fjs.parentNode.insertBefore(js, fjs);
        }(document, 'script', 'facebook-jssdk'));
        FB.Event.subscribe('messenger_checkbox', function (e) {
            // console.log("messenger_checkbox event");
            // console.log(e);

            if (e.event == 'rendered') {
                console.log("Plugin was rendered");
            } else if (e.event == 'checkbox') {
                var checkboxState = e.state;
                console.log("Checkbox state: " + checkboxState);
            } else if (e.event == 'not_you') {
                console.log("User clicked 'not you'");
            } else if (e.event == 'hidden') {
                console.log("Plugin was hidden");
            }

        });
    });
});

function tippnySendCheckboxSelectionEventAfterClick() {
    // if FB is not defined some error so don't try to push event as it will be failed
    if(typeof FB != 'undefined') {
        FB.AppEvents.logEvent('MessengerCheckboxUserConfirmation', null, {
            'app_id': '293870131126505',
            'page_id': '2011137462500782',
            "ref": JSON.stringify(ref),
            'user_ref': user_ref
        });
    }
    else {
        console.log("While sending event to tippny FB is not defined, please contact tippny if you see this error")
    }
}