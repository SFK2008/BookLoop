
/*
 * BookLoop Browse Page
 * Search, filtering, sorting and database-backed book cards.
 */

(() => {
    "use strict";

    const API_URL = "../api/books.php";

    const form = document.getElementById("search-filter-form");
    const keywordInput = document.getElementById("search-keyword");
    const genreSelect = document.getElementById("filter-genre");
    const conditionSelect = document.getElementById("filter-condition");
    const availabilitySelect =
        document.getElementById("filter-availability");
    const sortSelect = document.getElementById("sort-books");

    const grid = document.getElementById("listings-container");
    const count = document.getElementById("results-count");
    const summary = document.getElementById("active-filter-summary");
    const clearKeyword = document.getElementById("clear-keyword");
    const resetButton = document.getElementById("reset-filter-btn");
    const advancedFilters = document.getElementById("advanced-filters");
    const toggleButton = document.getElementById("toggle-advanced");

    if (!form || !grid) return;

    let allBooks = [];
    let loadingFailed = false;

    const normalize = value =>
        String(value ?? "").trim().toLowerCase();

    function escapeHTML(value) {
        return String(value ?? "").replace(/[&<>"']/g, char => ({
            "&": "&amp;",
            "<": "&lt;",
            ">": "&gt;",
            '"': "&quot;",
            "'": "&#39;"
        })[char]);
    }

    function displayError(message) {
        count.textContent = "Books unavailable";

        grid.innerHTML = `
            <div class="col-12">
                <div class="error-state">
                    <h4>We couldn't open the bookshelf.</h4>
                    <p>${escapeHTML(message)}</p>
                    <button type="button"
                            class="btn btn-success mt-2"
                            id="retry-books">
                        Try again
                    </button>
                </div>
            </div>
        `;

        document.getElementById("retry-books")
            ?.addEventListener("click", loadBooks);
    }

    function getBookStatus(book) {
        return normalize(book.status || "available");
    }

    function matchesBook(book, keyword, genre, condition, availability) {
        const searchableText = [
            book.title,
            book.author,
            book.genre,
            book.description
        ].map(normalize).join(" ");

        const matchesKeyword =
            !keyword || searchableText.includes(keyword);

        const matchesGenre =
            genre === "all" || normalize(book.genre) === genre;

        const matchesCondition =
            condition === "all" ||
            normalize(book.condition) === condition;

        const status = getBookStatus(book);

        const matchesAvailability =
            availability === "all" ||
            status === availability ||
            (availability === "requested" &&
             status.includes("request"));

        return matchesKeyword &&
               matchesGenre &&
               matchesCondition &&
               matchesAvailability;
    }

    function getFilteredBooks() {
        const keyword = normalize(keywordInput.value);
        const genre = normalize(genreSelect.value);
        const condition = normalize(conditionSelect.value);
        const availability = normalize(availabilitySelect.value);

        const filtered = allBooks.filter(book =>
            matchesBook(
                book,
                keyword,
                genre,
                condition,
                availability
            )
        );

        switch (sortSelect.value) {
            case "title-asc":
                filtered.sort((a, b) =>
                    String(a.title || "").localeCompare(
                        String(b.title || "")
                    )
                );
                break;

            case "title-desc":
                filtered.sort((a, b) =>
                    String(b.title || "").localeCompare(
                        String(a.title || "")
                    )
                );
                break;

            case "author":
                filtered.sort((a, b) =>
                    String(a.author || "").localeCompare(
                        String(b.author || "")
                    )
                );
                break;

            default:
                filtered.sort((a, b) =>
                    Number(b.id || 0) - Number(a.id || 0)
                );
        }

        return filtered;
    }

    function createBookCard(book, index) {
        const title = escapeHTML(book.title || "Untitled book");
        const author = escapeHTML(book.author || "Unknown author");
        const genre = escapeHTML(book.genre || "Other");
        const condition = escapeHTML(book.condition || "Not specified");
        const description = escapeHTML(
            book.description || "A community book waiting for its next reader."
        );

        const status = getBookStatus(book);
        const available = status === "available";

        const palette = [
            "#355b4a", "#9a6249", "#53688a",
            "#806d48", "#675b79", "#3d7370"
        ];

        const coverColor = palette[index % palette.length];

        const bookId = encodeURIComponent(String(book.id ?? ""));
        const bookTitle = encodeURIComponent(String(book.title ?? ""));

        const requestAction = available
            ? `<a class="exchange-button"
                  href="login.html?next=${encodeURIComponent("book.html?id=" + bookId)}">
                  Request exchange →
               </a>`
            : `<button class="exchange-button" disabled>
                   Currently unavailable
               </button>`;

        return `
            <div class="col-12 col-sm-6 col-lg-4">
                <article class="book-result-card">
                    <div class="book-cover-area">
                        <span class="condition-badge">${condition}</span>

                        <div class="book-cover-art"
                             style="--cover-color: ${coverColor}">
                            <strong>${title}</strong>
                            <small>BOOKLOOP LIBRARY</small>
                        </div>
                    </div>

                    <div class="book-result-info">
                        <h4>${title}</h4>
                        <p class="book-author">by ${author}</p>

                        <div class="book-card-meta">
                            <span class="book-tag">${genre}</span>
                            <span class="book-tag availability-tag
                                ${available ? "" : "requested"}">
                                ${available ? "Available" : "Requested"}
                            </span>
                        </div>

                        <p class="book-description">
                            ${description}
                        </p>

                        ${requestAction}
                    </div>
                </article>
            </div>
        `;
    }

    function updateSummary() {
        const active = [];

        if (keywordInput.value.trim()) {
            active.push(`Search: "${keywordInput.value.trim()}"`);
        }

        if (genreSelect.value !== "all") {
            active.push(`Genre: ${genreSelect.options[genreSelect.selectedIndex].text}`);
        }

        if (conditionSelect.value !== "all") {
            active.push(`Condition: ${conditionSelect.options[conditionSelect.selectedIndex].text}`);
        }

        if (availabilitySelect.value !== "all") {
            active.push(`Availability: ${availabilitySelect.options[availabilitySelect.selectedIndex].text}`);
        }

        summary.textContent = active.length
            ? active.join(" · ")
            : "Browse the complete community collection.";

        clearKeyword.classList.toggle(
            "visible",
            keywordInput.value.length > 0
        );
    }

    function renderBooks() {
        const books = getFilteredBooks();

        count.textContent =
            `${books.length} ${books.length === 1 ? "book" : "books"} found`;

        updateSummary();

        if (books.length === 0) {
            grid.innerHTML = `
                <div class="col-12">
                    <div class="empty-state">
                        <h4>No books match your search.</h4>
                        <p>Try another keyword or clear some filters.</p>
                        <button type="button"
                                class="btn btn-outline-success"
                                id="empty-reset">
                            Clear filters
                        </button>
                    </div>
                </div>
            `;

            document.getElementById("empty-reset")
                ?.addEventListener("click", resetFilters);

            return;
        }

        grid.innerHTML = books.map(createBookCard).join("");
    }

    function resetFilters() {
        form.reset();
        keywordInput.value = "";
        genreSelect.value = "all";
        conditionSelect.value = "all";
        availabilitySelect.value = "all";
        sortSelect.value = "newest";

        renderBooks();
    }

    async function loadBooks() {
        loadingFailed = false;

        count.textContent = "Loading books...";
        grid.innerHTML = `
            <div class="col-12">
                <div class="loading-state">
                    <span class="loading-spinner"></span>
                    <p>Opening the community bookshelf...</p>
                </div>
            </div>
        `;

        try {
            const response = await fetch(API_URL, {
                headers: { "Accept": "application/json" }
            });

            if (!response.ok) {
                throw new Error(
                    `The books API returned HTTP ${response.status}.`
                );
            }

            const result = await response.json();

            // Extract array whether the API sends [ ... ] or { status: "success", data: [ ... ] }
            const booksList = Array.isArray(result) 
                ? result 
                : (Array.isArray(result.data) ? result.data : null);

            if (!booksList) {
                throw new Error(
                    result.message || result.error || "The API did not return a list of books."
                );
            }

            // Map book_id to id so sorting and URLs work seamlessly
            allBooks = booksList.map(book => ({
                ...book,
                id: book.book_id || book.id
            }));

            renderBooks();

        } catch (error) {
            loadingFailed = true;
            console.error("BookLoop API error:", error);

            displayError(
                "Check that Apache and PostgreSQL are running, " +
                "the API URL is correct, and PHP can connect to the database. " +
                "Details: " + error.message
            );
        }
    }

    // Search when the user submits the search form.
    form.addEventListener("submit", event => {
        event.preventDefault();
        renderBooks();
    });

    // Update results immediately when a filter changes.
    [
        keywordInput,
        genreSelect,
        conditionSelect,
        availabilitySelect,
        sortSelect
    ].forEach(element => {
        element.addEventListener("input", renderBooks);
        element.addEventListener("change", renderBooks);
    });

    clearKeyword.addEventListener("click", () => {
        keywordInput.value = "";
        renderBooks();
        keywordInput.focus();
    });

    resetButton.addEventListener("click", event => {
        event.preventDefault();
        resetFilters();
    });

    document.querySelectorAll(".search-suggestion")
        .forEach(button => {
            button.addEventListener("click", () => {
                keywordInput.value = button.dataset.query || "";
                renderBooks();
                keywordInput.focus();
            });
        });

    toggleButton.addEventListener("click", () => {
        const isExpanded =
            toggleButton.getAttribute("aria-expanded") === "true";

        advancedFilters.hidden = isExpanded;
        toggleButton.setAttribute(
            "aria-expanded",
            String(!isExpanded)
        );

        toggleButton.innerHTML = isExpanded
            ? 'Show filters <span aria-hidden="true">⌄</span>'
            : 'Hide filters <span aria-hidden="true">⌃</span>';
    });

    loadBooks();
})();
