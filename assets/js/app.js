// Sample mock dataset matching PostgreSQL table specifications (SRS)
const mockBooks = [
    {
        book_id: 1,
        title: "Clean Code",
        author: "Robert C. Martin",
        genre: "Technology",
        condition: "Good",
        status: "Available",
        has_note: true,
        note_filename: "clean_code_summary.pdf"
    },
    {
        book_id: 2,
        title: "Dune",
        author: "Frank Herbert",
        genre: "Fiction",
        condition: "New",
        status: "Available",
        has_note: false,
        note_filename: null
    },
    {
        book_id: 3,
        title: "Sapiens: A Brief History of Humankind",
        author: "Yuval Noah Harari",
        genre: "History",
        condition: "Fair",
        status: "Available",
        has_note: true,
        note_filename: "sapiens_notes.pdf"
    }
];

// Execute when DOM content is fully loaded
document.addEventListener("DOMContentLoaded", () => {
    
    const container = document.getElementById("listings-container");

    // Only run search logic if on Browse/Index page
    if (container) {
        renderListings(mockBooks);

        // Bind Search Input & Filter Change Handlers
        const searchInput = document.getElementById("search-keyword");
        const genreSelect = document.getElementById("filter-genre");
        const conditionSelect = document.getElementById("filter-condition");
        const filterForm = document.getElementById("search-filter-form");

        function handleFilter() {
            const keyword = searchInput.value.toLowerCase().trim();
            const selectedGenre = genreSelect.value;
            const selectedCondition = conditionSelect.value;

            const filtered = mockBooks.filter(book => {
                const matchesKeyword = book.title.toLowerCase().includes(keyword) || 
                                       book.author.toLowerCase().includes(keyword);
                const matchesGenre = (selectedGenre === "all") || (book.genre === selectedGenre);
                const matchesCondition = (selectedCondition === "all") || (book.condition === selectedCondition);

                return matchesKeyword && matchesGenre && matchesCondition;
            });

            renderListings(filtered);
        }

        // Attach Event Listeners
        searchInput.addEventListener("input", handleFilter);
        genreSelect.addEventListener("change", handleFilter);
        conditionSelect.addEventListener("change", handleFilter);

        filterForm.addEventListener("reset", () => {
            setTimeout(() => renderListings(mockBooks), 50);
        });
    }
});

// Dynamic Card Rendering Function
function renderListings(books) {
    const container = document.getElementById("listings-container");
    container.innerHTML = "";

    if (books.length === 0) {
        container.innerHTML = `
            <div class="col-12 text-center py-5">
                <p class="text-muted fs-5">No books match your specified search criteria.</p>
            </div>`;
        return;
    }

    books.forEach(book => {
        const cardHTML = `
            <div class="col-md-6 col-lg-4">
                <div class="card h-100 shadow-sm position-relative">
                    <span class="badge bg-secondary badge-condition">${book.condition}</span>
                    <div class="card-body d-flex flex-column">
                        <h5 class="card-title me-5">${book.title}</h5>
                        <h6 class="card-subtitle mb-2 text-muted">By ${book.author}</h6>
                        <p class="card-text mb-1"><small class="text-primary">Genre: ${book.genre}</small></p>
                        <p class="card-text mb-3"><small class="text-success">Status: ${book.status}</small></p>
                        
                        <div class="mt-auto d-flex gap-2">
                            <button class="btn btn-outline-primary btn-sm flex-fill" onclick="requestExchange(${book.book_id})">
                                Request Exchange
                            </button>
                            ${book.has_note ? `
                                <a href="../uploads/${book.note_filename}" class="btn btn-outline-info btn-sm" download>
                                    Download Notes
                                </a>` : ''
                            }
                        </div>
                    </div>
                </div>
            </div>
        `;
        container.innerHTML += cardHTML;
    });
}

// User Interaction Mock Functions
function requestExchange(bookId) {
    alert(`Exchange request sent for Book ID: ${bookId}! (Backend integration will process this in Phase 7)`);
}