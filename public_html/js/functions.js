/**
 * Created by morussa on 5/11/2016.
 */
var url = location.href, Page, cover, debugElement, floater, message, spinner, closeButton = '<div class="generalCancel"><i class="fa fa-close"></i>Close</div>';

if (document.location.href.indexOf('https') != -1) {
    //We are secure
    var protocol = 'https';
} else {
    var protocol = 'http';
}
if (document.location.href.indexOf('dev') != -1 || document.location.href.match(/\/{2}\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3}/) != null) {
    //Local
    var parts = document.location.href.split('pi.embassyllc.dev');
    var local = true;
    var autolink = parts[0] + 'pi.embassyllc.dev';
    var cookiedomain = '.pi.embassyllc.dev';
    var cookiepath = '/';
    console.log('local');
} else {
    //Remote
    var local = false;
    var autolink = protocol + '://pi.embassyllc.com';
    var cookiedomain = '.pi.embassyllc.com';
    var cookiepath = '/';
    console.log('remote');
}

function checkForSpaces(s) {
    return /\s/g.test(s);
}

function coverMe(content, triggerCreate) {
    /*
     Produce the screen cover and optional content.
     showCover = (boolean).
     content = (string) html content.
     triggerCreate = (boolean) manually trigger the jqueryMobile create command.
     */
    spinner.hide();
    // triggerCreate = typeof triggerCreate == 'undefined' ? true : triggerCreate;
    if (content) {
        cover.show();
        floater.html('<div id="floaterContent">' + closeButton + '<p>' + content + '</p></div>');
        floater.show();
        /* if (triggerCreate) {
         floater.show().trigger("create");
         }*/
        floater.css("top", $(window).scrollTop());
    } else {
        floater.hide();
        cover.hide();
    }
}

function delete_cookie(name) {
    if (get_cookie(name)) {
        document.cookie = name + "=" +
            ((cookiepath) ? ";path=" + cookiepath : "") +
            ((cookiedomain) ? ";domain=" + cookiedomain : "") +
            ";expires=Thu, 01 Jan 1970 00:00:01 GMT";
    }
}

function deleteCookie(name) {
    document.cookie = name + '=; expires=Thu, 01 Jan 1970 00:00:01 GMT;';
}

function getCookie(cname) {
    var name = cname + "=";
    var ca = document.cookie.split(';');
    for (var i = 0; i < ca.length; i++) {
        var c = ca[i];
        while (c.charAt(0) == ' ') {
            c = c.substring(1);
        }
        if (c.indexOf(name) == 0) {
            return c.substring(name.length, c.length);
        }
    }
    return "";
}

function getCookieValue(a, b) {
    b = document.cookie.match('(^|;)\\s*' + a + '\\s*=\\s*([^;]+)');
    return b ? b.pop() : '';
}

function isNumeric(n){
    // Returns bool.
    return !isNaN(parseFloat(n)) && isFinite(n);
}

function setCookie(name, value, expires, path, domain) {
    /* Set cookies
     Can also be used to delete a cookie by using the same name, but "" for value and -1 for expires.
     Learn more at: http://www.w3resource.com/javascript/cookies/cookies-setting.php.
     name = (string) a single string with no commas, semicolons or whitespace characters.
     value = (string) a single string with no commas, semicolons or whitespace characters.
     expires = (int) the amount of time in days from the current time when the cookie will expire. If no value is set for expires, it will only last as long as the current session of the visitor, and will be automatically deleted when they close their browser.
     path = (string) By default the path value is ‘/’, meaning that the cookie is visible to all paths in a given domain. It's good practice to not assume the path to the site root will be set the way you want it by default, so set this manually to '/'.
     domain - (string) If you don’t specify the domain, it will belong to the page that set the cookie. Set the domain if you are using the cookie on a subdomain, like widgets.yoursite.com, where the cookie is set on the widgets subdomain but you need it to be accessible over the whole yoursite.com domain. If a domain is specified it must begin with a ".".
     */
    var today = new Date();
    today.setTime(today.getTime());
    if (expires) {
        expires = expires * 1000 * 60 * 60 * 24;
    }
    var expires_date = new Date(today.getTime() + (expires));
    var temp = name + "=" + escape(value) +
        ((expires) ? ";expires=" + expires_date.toGMTString() : "") +
        ((path) ? ";path=" + path : "/") + ((domain) ? ";domain=" + domain : "");
    // console.log('cookie attempt: ' + temp);
    document.cookie = temp;
}

function showMessage(messageText, fadeOut) {
    /**
     * Show a message at the top of the window.
     *
     * @param string    messageText The message displayed to the user. This also determines whether the message holder will show or hide.
     * @param boolean fadeOut   Determines whether the message holder will automatically fade to display:none or remain visible. Default is remain visible.
     *
     */
    if (messageText) {
        message.html(closeButton + '<p>' + messageText + '</p>');
        message.show();
        if (fadeOut) {
            message.delay(1500).fadeOut(3000, function () {
                message.hide();
            });
        }
    } else {
        message.hide();
    }
}

function spinnerShow(content) {
    if (typeof content !== 'undefined') {
        cover.show();
        spinner.show().html('<div style="position:fixed;width:100%"><div class="textCenter"><a href="' + window.location.href + '"><img alt="" src="' + autolink + '/images/spinner.png" style="width:60px;height:30px"></a><div class="bold textCenter textLarge">' + content + '</div></div></div>');
    } else {
        spinner.hide();
        cover.hide();
    }
}

$(document).ready(function () {
    Page = $("body"), cover = $("#cover"), debugElement = $("#debug"), floater = $("#floater"), message = $("#message"), spinner = $("#spinner");

    //if ($("#body div").html().indexOf("fa-caret-right") < 0) {
    $(".toggleButton, .toggleButtonInline").append('<span class="fa fa-chevron-up fa-rotate-90"></span>');
    //}

    Page.on("click", ".toggleButton, .toggleButtonInline", function () {
        /*
         Toggle the sections.
         There should be a containing element around the arrow img. That container needs a custom attribute of "toggleButton" The sibling to be toggled must have a class of "toggleMe".
         */
        var toggleMe = $(this).next('.toggleMe, .toggleMeNoOverlap');
        var arrow = $(this).children(".fa-chevron-up");
        /*$(".toggleMe").each(function () {
         if ($(this).is(":visible")) {
         //Close all toggles.
         $(this).slideUp(200);
         //var arrow = $(this).find(".fa");
         $("span").removeClass("fa-rotate-180");
         }
         });*/
        if (toggleMe.css('display') == 'none') {
            toggleMe.slideDown(200);
            arrow.toggleClass("fa-rotate-180", true);
        } else {
            toggleMe.slideUp();
            arrow.toggleClass("fa-rotate-180", false);
        }
    })

    //General cancel to close floaters and messages.
    Page.on("click", ".generalCancel", function () {
        floater.empty().hide();
        var hideCover = true;
        $(".floater").each(function () {
            if ($(this).css("display") != "none") {
                hideCover = false;
            }
        })
        message.hide();
        spinner.hide();
        if (hideCover) {
            cover.hide();
        }
    })
})