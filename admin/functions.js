function josxhaCopyToClipboard(text) {
    if (window.clipboardData && window.clipboardData.setData) {
        // IE specific code path to prevent textarea being shown while dialog is visible.
        return clipboardData.setData("Text", text);

    } else if (document.queryCommandSupported && document.queryCommandSupported("copy")) {
        var textarea = document.createElement("textarea");
        textarea.textContent = text;
        textarea.style.position = "fixed";  // Prevent scrolling to bottom of page in MS Edge.
        document.body.appendChild(textarea);
        textarea.select();
        try {
            return document.execCommand("copy");  // Security exception may be thrown by some browsers.
        } catch (ex) {
            console.warn("Copy to clipboard failed.", ex);
            return false;
        } finally {
            document.body.removeChild(textarea);
        }
    }
}

function getAnchorFromUrl() {
    let currentUrl = document.URL,
        urlParts   = currentUrl.split('#');
    return (urlParts.length > 1) ? urlParts[1] : null;
}

// switch tab when user clicks on another tab
function switchTab(event) {
    event.preventDefault();

    document.querySelector("ul.nav-tabs li.active").classList.remove("active");
    document.querySelector(".tab-pane.active").classList.remove("active");

    let clickedTab = event.currentTarget;
    let anchor = event.target;
    let activePaneID = anchor.getAttribute("href");

    clickedTab.classList.add("active");
    document.querySelector(activePaneID).classList.add("active");
}

// checks url if settings tab was opened
if (getAnchorFromUrl() === "settings") {
    document.querySelector("ul.nav-tabs li.active").classList.remove("active");
    document.querySelector(".tab-pane.active").classList.remove("active");

    document.getElementById("tabSettings").classList.add("active");
    document.querySelector("#settings").classList.add("active");
}