/**
 * BookLoop - Unified Frontend Application Logic
 * Adjust endpoint paths below if your PHP files are stored in a different folder.
 */
const API = {
    AUTH: "../api/auth.php",
    BOOKS: "../api/books.php",
    REQUESTS: "../api/requests.php",
    NOTES: "../api/notes.php"
};

document.addEventListener("DOMContentLoaded", () => {
    // 1. Browse Page (index.html)
    if (document.getElementById("listings-container")) {
        initBrowsePage();
    }

    // 2. Dashboard Page (dashboard.html)
    if (document.getElementById("exchange-requests-table")) {
        initDashboardPage();
    }

    // 3. Login Page (login.html)
    if (document.getElementById("login-form")) {
        initLoginPage();
    }

    // 4. Register Page (register.html)
    if (document.getElementById("register-form")) {
        initRegisterPage();
    }

    // 5. Global Logout Listener (binds to any nav link leading to login.html or marked as logout)
    bindLogoutHandler();
});

/* ==========================================================================
   1. BROWSE / SEARCH PAGE (index.html)
   ========================================================================== */
function initBrowsePage() {
    const container = document.getElementById("listings-container");
    const searchInput = document.getElementById("search-keyword");
    const genreSelect = document.getElementById("filter-genre");
    const conditionSelect = document.getElementById("filter-condition");
    const filterForm = document.getElementById("search-filter-form");

    let debounceTimer;
    function debounce(fn, delay = 300) {
        clearTimeout(debounceTimer);
        debounceTimer = setTimeout(fn, delay);
    }

    async function loadBooks() {
        const keyword = searchInput.value.trim();
        const genre = genreSelect.value;
        const condition = conditionSelect.value;

        const params = new URLSearchParams();
        if (keyword) params.append("keyword", keyword);
        if (genre && genre !== "all") params.append("genre", genre);
        if (condition && condition !== "all") params.append("condition", condition);

        container.innerHTML = `
            <div class="col-12 text-center py-5">
                <div class="spinner-border text-primary" role="status">
                    <span class="visually-hidden">Loading...</span>
                </div>
            </div>`;

        try {
            const res = await fetch(`${API.BOOKS}?${params.toString()}`);
            const result = await res.json();

            if (result.status === "success") {
                renderListings(result.data);
            } else {
                throw new Error(result.message || "Failed to load listings");
            }
        } catch (err) {
            console.error("Browse Error:", err);
            container.innerHTML = `
                <div class="col-12 text-center py-5 text-danger">
                    <p class="fs-5">Unable to load books. Please try again later.</p>
                </div>`;
        }
    }

    searchInput.addEventListener("input", () => debounce(loadBooks, 300));
    genreSelect.addEventListener("change", loadBooks);
    conditionSelect.addEventListener("change", loadBooks);
    filterForm.addEventListener("reset", () => setTimeout(loadBooks, 50));

    // Initial load
    loadBooks();
}

function renderListings(books) {
    const container = document.getElementById("listings-container");
    container.innerHTML = "";

    if (!books || books.length === 0) {
        container.innerHTML = `
            <div class="col-12 text-center py-5">
                <p class="text-muted fs-5">No books match your specified search criteria.</p>
            </div>`;
        return;
    }

    books.forEach(book => {
        const hasNote = Boolean(book.file_name);

        const cardHTML = `
            <div class="col-md-6 col-lg-4">
                <div class="card h-100 shadow-sm position-relative">
                    <span class="badge bg-secondary position-absolute top-0 end-0 m-3">${escapeHtml(book.condition)}</span>
                    <div class="card-body d-flex flex-column">
                        <h5 class="card-title me-5">${escapeHtml(book.title)}</h5>
                        <h6 class="card-subtitle mb-2 text-muted">By ${escapeHtml(book.author)}</h6>
                        ${book.owner_name ? `<p class="card-text mb-1"><small class="text-muted">Owner: ${escapeHtml(book.owner_name)}</small></p>` : ""}
                        <p class="card-text mb-1"><small class="text-primary">Genre: ${escapeHtml(book.genre)}</small></p>
                        <p class="card-text mb-3"><small class="text-success">Status: ${escapeHtml(book.status)}</small></p>
                        
                        <div class="mt-auto d-flex gap-2">
                            <button class="btn btn-outline-primary btn-sm flex-fill" onclick="requestExchange(${book.book_id})">
                                Request Exchange
                            </button>
                            ${hasNote ? `
                                <a href="../uploads/${encodeURIComponent(book.file_name)}" class="btn btn-outline-info btn-sm" download>
                                    Download Notes
                                </a>` : ""
                            }
                        </div>
                    </div>
                </div>
            </div>
        `;
        container.insertAdjacentHTML("beforeend", cardHTML);
    });
}

// Request exchange via API
async function requestExchange(bookId) {
    try {
        const res = await fetch(API.REQUESTS, {
            method: "POST",
            headers: { "Content-Type": "application/json" },
            body: JSON.stringify({ action: "create", book_id: bookId })
        });

        const data = await res.json();
        if (res.status === 401) {
            alert("You must be logged in to request an exchange.");
            window.location.href = "login.html";
            return;
        }

        if (data.status === "success") {
            alert(data.message);
        } else {
            alert(data.message || "Failed to submit request.");
        }
    } catch (err) {
        console.error("Exchange Request Error:", err);
        alert("Failed to connect to the server.");
    }
}

/* ==========================================================================
   2. DASHBOARD PAGE (dashboard.html)
   ========================================================================== */
function initDashboardPage() {
    const addBookForm = document.getElementById("add-book-form");
    const uploadNoteForm = document.getElementById("upload-note-form");

    // Fetch exchange requests
    loadDashboardRequests();

    // Form: Add New Physical Book
    if (addBookForm) {
        addBookForm.addEventListener("submit", async (e) => {
            e.preventDefault();
            const payload = {
                title: document.getElementById("book-title").value.trim(),
                author: document.getElementById("book-author").value.trim(),
                genre: document.getElementById("book-genre").value,
                condition: document.getElementById("book-condition").value
            };

            try {
                const res = await fetch(API.BOOKS, {
                    method: "POST",
                    headers: { "Content-Type": "application/json" },
                    body: JSON.stringify(payload)
                });
                const data = await res.json();

                if (res.status === 401) {
                    window.location.href = "login.html";
                    return;
                }

                if (data.status === "success") {
                    alert("Book listed successfully!");
                    addBookForm.reset();
                } else {
                    alert(data.message || "Could not list book.");
                }
            } catch (err) {
                console.error("Add Book Error:", err);
                alert("Server error occurred while adding book.");
            }
        });
    }

    // Form: Upload Digital Notes (Multipart FormData)
    if (uploadNoteForm) {
        uploadNoteForm.addEventListener("submit", async (e) => {
            e.preventDefault();
            const bookId = document.getElementById("note-book-id").value;
            const fileInput = document.getElementById("note-file");

            if (!fileInput.files.length) {
                alert("Please select a file to upload.");
                return;
            }

            const formData = new FormData();
            formData.append("book_id", bookId);
            formData.append("note_file", fileInput.files[0]);

            try {
                const res = await fetch(API.NOTES, {
                    method: "POST",
                    body: formData // Browser sets proper multipart/form-data boundary
                });
                const data = await res.json();

                if (res.status === 401) {
                    window.location.href = "login.html";
                    return;
                }

                if (data.status === "success") {
                    alert(data.message);
                    uploadNoteForm.reset();
                } else {
                    alert(data.message || "Failed to upload note.");
                }
            } catch (err) {
                console.error("Upload Note Error:", err);
                alert("Server error occurred during upload.");
            }
        });
    }
}

// Fetch Incoming Requests for Owner
async function loadDashboardRequests() {
    const tableBody = document.getElementById("exchange-requests-table");
    if (!tableBody) return;

    try {
        const res = await fetch(API.REQUESTS);
        if (res.status === 401) {
            window.location.href = "login.html";
            return;
        }

        const result = await res.json();
        if (result.status !== "success") throw new Error(result.message);

        tableBody.innerHTML = "";
        const requests = result.data;

        if (requests.length === 0) {
            tableBody.innerHTML = `<tr><td colspan="4" class="text-center text-muted py-3">No incoming requests.</td></tr>`;
            return;
        }

        requests.forEach(req => {
            const badgeClass = req.status === "Approved" ? "bg-success" : (req.status === "Rejected" ? "bg-danger" : "bg-warning text-dark");
            const isPending = req.status === "Pending";

            const row = `
                <tr>
                    <td>${escapeHtml(req.title)}</td>
                    <td>${escapeHtml(req.requester_name)}</td>
                    <td><span class="badge ${badgeClass}">${escapeHtml(req.status)}</span></td>
                    <td>
                        ${isPending ? `
                            <button class="btn btn-sm btn-success me-1" onclick="updateRequestStatus(${req.exchange_id}, 'Approved')">Accept</button>
                            <button class="btn btn-sm btn-outline-danger" onclick="updateRequestStatus(${req.exchange_id}, 'Rejected')">Reject</button>
                        ` : `<span class="text-muted small">Completed</span>`}
                    </td>
                </tr>
            `;
            tableBody.insertAdjacentHTML("beforeend", row);
        });
    } catch (err) {
        console.error("Fetch Requests Error:", err);
        tableBody.innerHTML = `<tr><td colspan="4" class="text-center text-danger">Failed to load requests.</td></tr>`;
    }
}

// Accept or Reject an Exchange Request
async function updateRequestStatus(exchangeId, newStatus) {
    try {
        const res = await fetch(API.REQUESTS, {
            method: "POST",
            headers: { "Content-Type": "application/json" },
            body: JSON.stringify({
                action: "update_status",
                exchange_id: exchangeId,
                status: newStatus
            })
        });

        const data = await res.json();
        if (data.status === "success") {
            alert(data.message);
            loadDashboardRequests(); // Reload table
        } else {
            alert(data.message || "Failed to update status.");
        }
    } catch (err) {
        console.error("Update Request Error:", err);
        alert("Server error occurred.");
    }
}

/* ==========================================================================
   3. AUTHENTICATION: LOGIN & REGISTER
   ========================================================================== */
function initLoginPage() {
    const form = document.getElementById("login-form");
    form.addEventListener("submit", async (e) => {
        e.preventDefault();
        const email = document.getElementById("login-email").value.trim();
        const password = document.getElementById("login-password").value;

        try {
            const res = await fetch(API.AUTH, {
                method: "POST",
                headers: { "Content-Type": "application/json" },
                body: JSON.stringify({ action: "login", email, password })
            });

            const data = await res.json();
            if (data.status === "success") {
                window.location.href = "dashboard.html";
            } else {
                alert(data.message || "Invalid credentials.");
            }
        } catch (err) {
            console.error("Login Error:", err);
            alert("An error occurred while logging in.");
        }
    });
}

function initRegisterPage() {
    const form = document.getElementById("register-form");
    form.addEventListener("submit", async (e) => {
        e.preventDefault();
        const name = document.getElementById("register-name").value.trim();
        const email = document.getElementById("register-email").value.trim();
        const password = document.getElementById("register-password").value;

        try {
            const res = await fetch(API.AUTH, {
                method: "POST",
                headers: { "Content-Type": "application/json" },
                body: JSON.stringify({ action: "register", name, email, password })
            });

            const data = await res.json();
            if (data.status === "success") {
                alert("Account created successfully! Please sign in.");
                window.location.href = "login.html";
            } else {
                alert(data.message || "Registration failed.");
            }
        } catch (err) {
            console.error("Register Error:", err);
            alert("An error occurred while registering.");
        }
    });
}

// Global logout handler
function bindLogoutHandler() {
    const logoutLinks = document.querySelectorAll('a[href="login.html"].text-danger, #logout-btn');
    logoutLinks.forEach(link => {
        link.addEventListener("click", async (e) => {
            e.preventDefault();
            try {
                await fetch(`${API.AUTH}?action=logout`);
            } catch (err) {
                console.error("Logout Error:", err);
            } finally {
                window.location.href = "login.html";
            }
        });
    });
}

/* ==========================================================================
   HELPERS
   ========================================================================== */
function escapeHtml(str) {
    if (!str) return "";
    return String(str)
        .replace(/&/g, "&amp;")
        .replace(/</g, "&lt;")
        .replace(/>/g, "&gt;")
        .replace(/"/g, "&quot;")
        .replace(/'/g, "&#039;");
}
