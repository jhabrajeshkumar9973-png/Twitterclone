// =========================================================================
// Twitter Clone - Frontend Logic Engine (Character Counter & Input Guards)
// =========================================================================

document.addEventListener("DOMContentLoaded", function() {

    function loadMessages() {
        const chatBox = document.getElementById("chatBox") || document.getElementById("messageBox");
        if (!chatBox || typeof window.receiverId === "undefined" || !window.receiverId) {
            return;
        }

        fetch("get_messages.php?user_id=" + window.receiverId)
            .then(function(response) {
                return response.text();
            })
            .then(function(html) {
                if (chatBox) {
                    chatBox.innerHTML = html;
                    chatBox.scrollTop = chatBox.scrollHeight;
                }
            });
    }

    const loginForm = document.getElementById("loginForm");

    if (loginForm) {
        loginForm.addEventListener("submit", function(event) {
            event.preventDefault();

            const submitBtn = loginForm.querySelector("button[type='submit']");
            const errorBox = document.querySelector(".auth-error");
            const formData = new FormData(loginForm);

            if (submitBtn) {
                submitBtn.disabled = true;
            }

            fetch(loginForm.getAttribute("action") || "login.php", {
                method: "POST",
                headers: {
                    "X-Requested-With": "XMLHttpRequest"
                },
                body: formData
            })
            .then(function(response) {
                return response.text().then(function(text) {
                    try {
                        return JSON.parse(text);
                    } catch (error) {
                        return { success: false, message: "Something went wrong." };
                    }
                });
            })
            .then(function(data) {
                if (data && data.success) {
                    window.location.href = "index.php";
                    return;
                }

                const message = (data && data.message) || "Invalid email or password.";
                if (errorBox) {
                    errorBox.textContent = message;
                    errorBox.style.display = "block";
                }
                window.alert(message);
            })
            .catch(function() {
                if (errorBox) {
                    errorBox.textContent = "Unable to log in right now.";
                    errorBox.style.display = "block";
                }
                window.alert("Unable to log in right now.");
            })
            .finally(function() {
                if (submitBtn) {
                    submitBtn.disabled = false;
                }
            });
        });
    }

    const sendBtn = document.getElementById("sendBtn");
    const messageInput = document.getElementById("messageInput");

    if (sendBtn && messageInput) {
        function sendMessage() {
            const message = messageInput.value.trim();
            if (!message) return;

            fetch("send_message.php", {
                method: "POST",
                headers: {
                    "Content-Type": "application/x-www-form-urlencoded;charset=UTF-8",
                    "X-Requested-With": "XMLHttpRequest"
                },
                body: "receiver_id=" + window.receiverId + "&message=" + encodeURIComponent(message)
            })
            .then(function(response) {
                return response.text();
            })
            .then(function(result) {
                if (result.trim() === "success") {
                    messageInput.value = "";
                    loadMessages();
                } else {
                    alert("Message could not be sent.");
                }
                sendBtn.disabled = false;
            })
            .catch(function() {
                alert("Message could not be sent.");
                sendBtn.disabled = false;
            });
        }

        sendBtn.addEventListener("click", function(event) {
            event.preventDefault();
            if (sendBtn.disabled) return;
            sendBtn.disabled = true;
            sendMessage();
        });

        messageInput.addEventListener("keydown", function(event) {
            if (event.key === "Enter") {
                event.preventDefault();
                if (sendBtn.disabled) return;
                sendBtn.disabled = true;
                sendMessage();
            }
        });

        loadMessages();
        setInterval(loadMessages, 1500);
    }

    document.addEventListener("click", function(event) {
        document.querySelectorAll(".tweet-menu-wrap.open").forEach(function(menuWrap) {
            if (!menuWrap.contains(event.target)) {
                menuWrap.classList.remove("open");
            }
        });
    });

    document.querySelectorAll(".tweet-menu-btn").forEach(function(button) {
        button.addEventListener("click", function(event) {
            event.stopPropagation();
            const menuWrap = button.closest(".tweet-menu-wrap");
            if (!menuWrap) return;

            document.querySelectorAll(".tweet-menu-wrap.open").forEach(function(openMenu) {
                if (openMenu !== menuWrap) {
                    openMenu.classList.remove("open");
                }
            });

            menuWrap.classList.toggle("open");
        });
    });

    document.querySelectorAll(".edit-action").forEach(function(editButton) {
        editButton.addEventListener("click", function(event) {
            event.preventDefault();
            const menuWrap = editButton.closest(".tweet-menu-wrap");
            const editForm = menuWrap ? menuWrap.parentElement.querySelector(".tweet-edit-form") : null;

            if (!editForm) return;

            document.querySelectorAll(".tweet-edit-form.show").forEach(function(openForm) {
                if (openForm !== editForm) {
                    openForm.classList.remove("show");
                    const otherText = openForm.closest(".tweet")?.querySelector(".tweet-text");
                    if (otherText) {
                        otherText.style.display = "block";
                    }
                }
            });

            if (menuWrap) {
                menuWrap.classList.remove("open");
            }

            const tweetText = editForm.closest(".tweet")?.querySelector(".tweet-text");
            if (tweetText) {
                tweetText.style.display = "none";
            }

            editForm.classList.add("show");
            const textarea = editForm.querySelector("textarea");
            if (textarea) {
                textarea.focus();
                textarea.setSelectionRange(textarea.value.length, textarea.value.length);
            }
        });
    });

    document.querySelectorAll(".tweet-cancel-btn").forEach(function(cancelButton) {
        cancelButton.addEventListener("click", function() {
            const editForm = cancelButton.closest(".tweet-edit-form");
            if (editForm) {
                editForm.classList.remove("show");
                const tweetText = editForm.closest(".tweet")?.querySelector(".tweet-text");
                if (tweetText) {
                    tweetText.style.display = "block";
                }
            }
        });
    });

    // 1. DOM Elements ko select karein
    const tweetInput = document.getElementById("tweetInput");
    const charCount = document.getElementById("charCount");
    const tweetBtn = document.querySelector(".tweet-box-footer .tweet-btn");

    // Check lagayein ki elements page par exist karte hain ya nahi (Errors se bachne ke liye)
    if (tweetInput && charCount && tweetBtn) {
        
        const maxCharacters = 280;

        // 2. Input Event Listener binding (Har key-press par trigger hoga)
        tweetInput.addEventListener("input", function() {
            
            const currentLength = tweetInput.value.length;
            const remaining = maxCharacters - currentLength;

            // Display update karein
            charCount.textContent = remaining;

            // 3. UI Color aur Button Disable States Rules
            if (remaining < 0) {
                // Limit cross hone par text red karein aur button block karein
                charCount.style.color = "#f4212e"; 
                tweetBtn.disabled = true;
                tweetBtn.style.opacity = "0.5";
                tweetBtn.style.cursor = "not-allowed";
            } 
            else if (remaining <= 20) {
                // Jab sirf 20 characters bache hon, toh warning orange color dikhayein
                charCount.style.color = "#ffd400";
                tweetBtn.disabled = false;
                tweetBtn.style.opacity = "1";
                tweetBtn.style.cursor = "pointer";
            } 
            else {
                // Normal state me Twitter gray color rakhein
                charCount.style.color = "#536471";
                tweetBtn.disabled = false;
                tweetBtn.style.opacity = "1";
                tweetBtn.style.cursor = "pointer";
            }
        });
    }
});