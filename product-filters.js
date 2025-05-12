document.addEventListener('DOMContentLoaded', function() {
    // Sample product data
    const products = [
        {
            id: 1,
            name: 'Kask HJC RPHA 11 Carbon',
            category: 'kaski',
            brand: 'hjc',
            price: 2499,
            rating: 5,
            availability: 'in-stock',
            image: 'https://readdy.ai/api/search-image?query=HJC%20RPHA%2011%20Carbon%20helmet%20motorcycle%20professional%20product%20photography&width=400&height=300&seq=1',
            discount: 15,
            oldPrice: 2999
        },
        {
            id: 2,
            name: 'Kurtka Dainese Racing 4',
            category: 'odziez',
            brand: 'dainese',
            price: 3899,
            rating: 5,
            availability: 'in-stock',
            image: 'https://readdy.ai/api/search-image?query=Dainese%20Racing%204%20leather%20motorcycle%20jacket%20professional%20product%20photography&width=400&height=300&seq=2'
        },
        {
            id: 3,
            name: 'Olej silnikowy Motul 5100 4T 10W-40 4L',
            category: 'oleje',
            brand: 'motul',
            price: 149,
            rating: 4,
            availability: 'in-stock',
            image: 'https://readdy.ai/api/search-image?query=Motul%205100%204T%2010W-40%20motorcycle%20oil%204L%20bottle%20professional%20product%20photography&width=400&height=300&seq=3'
        },
        {
            id: 4,
            name: 'Spodnie Alpinestars GP Plus V3',
            category: 'odziez',
            brand: 'alpinestars',
            price: 1899,
            rating: 4,
            availability: 'in-stock',
            image: 'https://readdy.ai/api/search-image?query=Alpinestars%20GP%20Plus%20V3%20leather%20motorcycle%20pants%20professional%20product%20photography&width=400&height=300&seq=4'
        },
        {
            id: 5,
            name: 'Kask Shoei GT-Air 2',
            category: 'kaski',
            brand: 'shoei',
            price: 2699,
            rating: 5,
            availability: '2-3-days',
            image: 'https://readdy.ai/api/search-image?query=Shoei%20GT-Air%202%20motorcycle%20helmet%20professional%20product%20photography&width=400&height=300&seq=5',
            discount: 10,
            oldPrice: 2999
        },
        {
            id: 6,
            name: 'Olej silnikowy Castrol Power 1 Racing 4T 10W-50 4L',
            category: 'oleje',
            brand: 'castrol',
            price: 169,
            rating: 4,
            availability: 'in-stock',
            image: 'https://readdy.ai/api/search-image?query=Castrol%20Power%201%20Racing%204T%2010W-50%20motorcycle%20oil%204L%20bottle%20professional%20product%20photography&width=400&height=300&seq=6'
        },
        {
            id: 7,
            name: 'Rękawice Alpinestars Stella SP-8 v3',
            category: 'odziez',
            brand: 'alpinestars',
            price: 479,
            rating: 4,
            availability: 'in-stock',
            image: 'https://readdy.ai/api/search-image?query=Alpinestars%20Stella%20SP-8%20v3%20motorcycle%20gloves%20professional%20product%20photography&width=400&height=300&seq=7'
        },
        {
            id: 8,
            name: 'Akumulator Yuasa YTX14-BS',
            category: 'akumulatory',
            brand: 'yuasa',
            price: 429,
            rating: 5,
            availability: '2-3-days',
            image: 'https://readdy.ai/api/search-image?query=Yuasa%20YTX14-BS%20motorcycle%20battery%20professional%20product%20photography&width=400&height=300&seq=8'
        },
        {
            id: 9,
            name: 'Łańcuch DID 525 VX3',
            category: 'czesci',
            brand: 'did',
            price: 399,
            rating: 4,
            availability: '4-7-days',
            image: 'https://readdy.ai/api/search-image?query=DID%20525%20VX3%20motorcycle%20chain%20professional%20product%20photography&width=400&height=300&seq=9'
        },
        {
            id: 10,
            name: 'Interkom Sena 30K',
            category: 'akcesoria',
            brand: 'sena',
            price: 1299,
            rating: 4,
            availability: '4-7-days',
            image: 'https://readdy.ai/api/search-image?query=Sena%2030K%20motorcycle%20intercom%20professional%20product%20photography&width=400&height=300&seq=10'
        },
        {
            id: 11,
            name: 'Opona Michelin Pilot Power 5 180/55 ZR17',
            category: 'czesci',
            brand: 'michelin',
            price: 599,
            rating: 5,
            availability: '2-3-days',
            image: 'https://readdy.ai/api/search-image?query=Michelin%20Pilot%20Power%205%20180/55%20ZR17%20motorcycle%20tire%20professional%20product%20photography&width=400&height=300&seq=11'
        },
        {
            id: 12,
            name: 'Kamera GoPro Hero 11 Black',
            category: 'akcesoria',
            brand: 'gopro',
            price: 1999,
            rating: 5,
            availability: 'in-stock',
            image: 'https://readdy.ai/api/search-image?query=GoPro%20Hero%2011%20Black%20action%20camera%20professional%20product%20photography&width=400&height=300&seq=12'
        },
        {
            id: 13,
            name: 'Kask AGV Pista GP RR',
            category: 'kaski',
            brand: 'agv',
            price: 4999,
            rating: 5,
            availability: '2-3-days',
            image: 'https://readdy.ai/api/search-image?query=AGV%20Pista%20GP%20RR%20motorcycle%20helmet%20professional%20product%20photography&width=400&height=300&seq=13'
        },
        {
            id: 14,
            name: 'Kurtka Alpinestars Missile Tech-Air',
            category: 'odziez',
            brand: 'alpinestars',
            price: 5499,
            rating: 5,
            availability: 'in-stock',
            image: 'https://readdy.ai/api/search-image?query=Alpinestars%20Missile%20Tech-Air%20motorcycle%20jacket%20professional%20product%20photography&width=400&height=300&seq=14',
            discount: 10,
            oldPrice: 6099
        },
        {
            id: 15,
            name: 'Kask Bell SRT Modular',
            category: 'kaski',
            brand: 'bell',
            price: 1599,
            rating: 4,
            availability: 'in-stock',
            image: 'https://readdy.ai/api/search-image?query=Bell%20SRT%20Modular%20motorcycle%20helmet%20professional%20product%20photography&width=400&height=300&seq=15'
        },
        {
            id: 16,
            name: 'Buty Forma Adventure',
            category: 'odziez',
            brand: 'forma',
            price: 899,
            rating: 4,
            availability: 'in-stock',
            image: 'https://readdy.ai/api/search-image?query=Forma%20Adventure%20motorcycle%20boots%20professional%20product%20photography&width=400&height=300&seq=16'
        },
        {
            id: 17,
            name: 'Olej silnikowy Liqui Moly Motorbike 4T 10W-40 4L',
            category: 'oleje',
            brand: 'liqui_moly',
            price: 179,
            rating: 4,
            availability: 'in-stock',
            image: 'https://readdy.ai/api/search-image?query=Liqui%20Moly%20Motorbike%204T%2010W-40%20motorcycle%20oil%20professional%20product%20photography&width=400&height=300&seq=17'
        },
        {
            id: 18,
            name: 'Kurtka Rev\'it Sand 4',
            category: 'odziez',
            brand: 'revit',
            price: 1849,
            rating: 5,
            availability: '2-3-days',
            image: 'https://readdy.ai/api/search-image?query=Rev%27it%20Sand%204%20motorcycle%20jacket%20professional%20product%20photography&width=400&height=300&seq=18'
        },
        {
            id: 19,
            name: 'Akumulator Varta YTX20L-BS',
            category: 'akumulatory',
            brand: 'varta',
            price: 499,
            rating: 4,
            availability: 'in-stock',
            image: 'https://readdy.ai/api/search-image?query=Varta%20YTX20L-BS%20motorcycle%20battery%20professional%20product%20photography&width=400&height=300&seq=19'
        },
        {
            id: 20,
            name: 'Opona Continental ContiRoad 120/70 ZR17',
            category: 'czesci',
            brand: 'continental',
            price: 499,
            rating: 4,
            availability: '2-3-days',
            image: 'https://readdy.ai/api/search-image?query=Continental%20ContiRoad%20120/70%20ZR17%20motorcycle%20tire%20professional%20product%20photography&width=400&height=300&seq=20'
        },
        {
            id: 21,
            name: 'Tłumik Akrapovic Slip-On dla Yamaha MT-09',
            category: 'czesci',
            brand: 'akrapovic',
            price: 2999,
            rating: 5,
            availability: '4-7-days',
            image: 'https://readdy.ai/api/search-image?query=Akrapovic%20Slip-On%20exhaust%20for%20Yamaha%20MT-09%20professional%20product%20photography&width=400&height=300&seq=21'
        },
        {
            id: 22,
            name: 'Kask Schuberth C4 Pro',
            category: 'kaski',
            brand: 'schuberth',
            price: 2799,
            rating: 5,
            availability: 'in-stock',
            image: 'https://readdy.ai/api/search-image?query=Schuberth%20C4%20Pro%20motorcycle%20helmet%20professional%20product%20photography&width=400&height=300&seq=22'
        },
        {
            id: 23,
            name: 'Spodnie Rukka AirAll',
            category: 'odziez',
            brand: 'rukka',
            price: 1899,
            rating: 4,
            availability: '2-3-days',
            image: 'https://readdy.ai/api/search-image?query=Rukka%20AirAll%20motorcycle%20pants%20professional%20product%20photography&width=400&height=300&seq=23'
        },
        {
            id: 24,
            name: 'Nawigacja Garmin Zumo XT',
            category: 'akcesoria',
            brand: 'garmin',
            price: 1899,
            rating: 5,
            availability: 'in-stock',
            image: 'https://readdy.ai/api/search-image?query=Garmin%20Zumo%20XT%20motorcycle%20GPS%20navigation%20professional%20product%20photography&width=400&height=300&seq=24'
        }
    ];

    // Pagination state
    let currentPage = 1;
    const productsPerPage = 12;
    let filteredProducts = [...products]; // Store filtered products for pagination

    // Function to render product cards with pagination
    function renderProducts(products, page = 1) {
        const grid = document.getElementById('productsGrid');
        if (!grid) return;
        
        // Store the filtered products for pagination
        filteredProducts = [...products];
        
        // Calculate pagination
        const totalPages = Math.ceil(products.length / productsPerPage);
        const start = (page - 1) * productsPerPage;
        const end = start + productsPerPage;
        const paginatedProducts = products.slice(start, end);
        
        grid.innerHTML = '';
        
        // Render only the products for the current page
        paginatedProducts.forEach(product => {
            const card = createProductCard(product);
            grid.appendChild(card);
        });
        
        // Update pagination UI
        updatePagination(totalPages, page);
    }

    // Function to update pagination UI
    function updatePagination(totalPages, currentPage) {
        // Update page count text
        const pageCountText = document.querySelector('.mt-10 .text-gray-600');
        if (pageCountText) {
            pageCountText.textContent = `Strona ${currentPage} z ${totalPages}`;
        }
        
        // Clear existing pagination buttons except first, last and arrows
        const paginationContainer = document.querySelector('.mt-10 .flex.items-center.space-x-2');
        if (!paginationContainer) return;
        
        // Get arrow buttons
        const prevButton = paginationContainer.querySelector('button:first-child');
        const nextButton = paginationContainer.querySelector('button:last-child');
        
        // Rebuild pagination buttons
        paginationContainer.innerHTML = '';
        
        // Add prev button
        paginationContainer.appendChild(createPaginationButton('prev', currentPage <= 1, '<i class="ri-arrow-left-s-line"></i>'));
        
        // Add page numbers
        const maxButtons = 5; // Maximum number of page buttons to show
        let startPage = Math.max(1, currentPage - Math.floor(maxButtons / 2));
        let endPage = Math.min(totalPages, startPage + maxButtons - 1);
        
        // Adjust if we're at the end
        if (endPage - startPage + 1 < maxButtons && startPage > 1) {
            startPage = Math.max(1, endPage - maxButtons + 1);
        }
        
        // First page
        if (startPage > 1) {
            paginationContainer.appendChild(createPaginationButton(1, false));
        }
        
        // Ellipsis if needed
        if (startPage > 2) {
            const ellipsis = document.createElement('span');
            ellipsis.className = 'px-2';
            ellipsis.textContent = '...';
            paginationContainer.appendChild(ellipsis);
        }
        
        // Page numbers
        for (let i = startPage; i <= endPage; i++) {
            paginationContainer.appendChild(createPaginationButton(i, false, null, i === currentPage));
        }
        
        // Ellipsis if needed
        if (endPage < totalPages - 1) {
            const ellipsis = document.createElement('span');
            ellipsis.className = 'px-2';
            ellipsis.textContent = '...';
            paginationContainer.appendChild(ellipsis);
        }
        
        // Last page
        if (endPage < totalPages) {
            paginationContainer.appendChild(createPaginationButton(totalPages, false));
        }
        
        // Add next button
        paginationContainer.appendChild(createPaginationButton('next', currentPage >= totalPages, '<i class="ri-arrow-right-s-line"></i>'));
    }

    // Function to create a pagination button
    function createPaginationButton(page, disabled, html = null, active = false) {
        const button = document.createElement('button');
        button.className = 'pagination-btn';
        
        if (active) {
            button.classList.add('active');
        }
        
        if (disabled) {
            button.disabled = true;
        }
        
        if (html) {
            button.innerHTML = html;
        } else {
            button.textContent = page;
        }
        
        if (!disabled) {
            button.addEventListener('click', function() {
                if (page === 'prev') {
                    goToPage(currentPage - 1);
                } else if (page === 'next') {
                    goToPage(currentPage + 1);
                } else {
                    goToPage(page);
                }
            });
        }
        
        return button;
    }

    // Function to go to a specific page
    function goToPage(page) {
        currentPage = page;
        renderProducts(filteredProducts, currentPage);
        
        // Scroll back to top of products if needed
        const productsSection = document.querySelector('.lg\\:w-3\\/4');
        if (productsSection) {
            window.scrollTo({
                top: productsSection.offsetTop - 100,
                behavior: 'smooth'
            });
        }
    }

    // Function to create a product card
    function createProductCard(product) {
        const card = document.createElement('div');
        card.className = 'bg-white rounded-lg shadow-sm overflow-hidden hover:shadow-md transition group';
        
        let priceHTML = '';
        if (product.discount) {
            priceHTML = `
                <div class="flex items-baseline gap-2">
                    <span class="text-lg font-bold text-gray-900">${formatPrice(product.price)} zł</span>
                    <span class="text-sm text-gray-500 line-through">${formatPrice(product.oldPrice)} zł</span>
                    <span class="bg-primary text-white text-xs font-medium px-2 py-1 rounded">-${product.discount}%</span>
                </div>
            `;
        } else {
            priceHTML = `<span class="text-lg font-bold text-gray-900">${formatPrice(product.price)} zł</span>`;
        }
        
        // Create stars for rating
        let starsHTML = '';
        for (let i = 1; i <= 5; i++) {
            if (i <= product.rating) {
                starsHTML += '<i class="ri-star-fill text-yellow-400"></i>';
            } else {
                starsHTML += '<i class="ri-star-line text-yellow-400"></i>';
            }
        }
        
        card.innerHTML = `
            <div class="relative">
                <img src="${product.image}" alt="${product.name}" class="w-full h-60 object-cover object-center">
                <button class="absolute top-3 right-3 w-8 h-8 flex items-center justify-center bg-white rounded-full text-gray-400 hover:text-primary transition opacity-0 group-hover:opacity-100">
                    <i class="ri-heart-line"></i>
                </button>
            </div>
            <div class="p-4">
                <h3 class="font-medium text-gray-900 mb-1">${product.name}</h3>
                <div class="flex mb-2">
                    ${starsHTML}
                </div>
                <div class="flex justify-between items-center">
                    ${priceHTML}
                    <a href="#" class="bg-primary text-white px-4 py-2 rounded-button text-sm font-medium hover:bg-opacity-90 transition">
                        Kup teraz
                    </a>
                </div>
            </div>
        `;
        
        return card;
    }

    // Helper function to format price
    function formatPrice(price) {
        return price.toString().replace(/\B(?=(\d{3})+(?!\d))/g, " ");
    }

    // Update the counts of products for each filter option based on current filters
    function updateFilterCounts(filtered) {
        // Use full product list if not specified
        const productsToCount = filtered || products;
        
        // Calculate counts based on active filters
        // Update category counts
        const categoryElements = document.querySelectorAll('input[data-filter="category"]');
        categoryElements.forEach(checkbox => {
            if (checkbox.value === '') return; // Skip "All products" option
            
            const categoryValue = checkbox.value;
            // Count products that match this category and all other active filters
            let count = 0;
            
            if (filtered) {
                // If filters applied, just show how many products from the filtered set match this category
                count = productsToCount.filter(product => product.category === categoryValue).length;
            } else {
                // If no filters applied, show total count for this category
                count = products.filter(product => product.category === categoryValue).length;
            }
            
            // Find the label text and update count
            const label = checkbox.closest('label');
            if (label) {
                const labelText = label.textContent.trim().replace(/\(\d+\)$/, '').trim();
                label.childNodes.forEach(node => {
                    if (node.nodeType === Node.TEXT_NODE && node.textContent.trim()) {
                        node.textContent = `${labelText} (${count})`;
                    }
                });
            }
        });
        
        // Update brand counts
        const brandElements = document.querySelectorAll('input[data-filter="brand"]');
        brandElements.forEach(checkbox => {
            const brandValue = checkbox.value;
            let count = 0;
            
            if (filtered) {
                count = productsToCount.filter(product => product.brand === brandValue).length;
            } else {
                count = products.filter(product => product.brand === brandValue).length;
            }
            
            // Find the label text and update count
            const label = checkbox.closest('label');
            if (label) {
                const labelText = label.textContent.trim().replace(/\(\d+\)$/, '').trim();
                label.childNodes.forEach(node => {
                    if (node.nodeType === Node.TEXT_NODE && node.textContent.trim()) {
                        node.textContent = `${labelText} (${count})`;
                    }
                });
            }
        });
        
        // Update rating counts
        const ratingElements = document.querySelectorAll('input[data-filter="rating"]');
        ratingElements.forEach(checkbox => {
            const ratingValue = parseInt(checkbox.value);
            let count = 0;
            
            if (filtered) {
                count = productsToCount.filter(product => product.rating === ratingValue).length;
            } else {
                count = products.filter(product => product.rating === ratingValue).length;
            }
            
            // Find the span with count and update it
            const label = checkbox.closest('label');
            if (label) {
                const countSpan = label.querySelector('span.text-gray-600');
                if (countSpan) {
                    countSpan.textContent = `(${count})`;
                }
            }
        });
        
        // Update availability counts
        const availabilityElements = document.querySelectorAll('input[data-filter="availability"]');
        availabilityElements.forEach(checkbox => {
            const availabilityValue = checkbox.value;
            let count = 0;
            
            if (filtered) {
                count = productsToCount.filter(product => product.availability === availabilityValue).length;
            } else {
                count = products.filter(product => product.availability === availabilityValue).length;
            }
            
            // Find the label text and update count
            const label = checkbox.closest('label');
            if (label) {
                const labelText = label.textContent.trim().replace(/\(\d+\)$/, '').trim();
                label.childNodes.forEach(node => {
                    if (node.nodeType === Node.TEXT_NODE && node.textContent.trim()) {
                        node.textContent = `${labelText} (${count})`;
                    }
                });
            }
        });
    }

    // Filter functions
    function filterProducts() {
        const minPrice = parseInt(document.getElementById('min-price').value) || 0;
        const maxPrice = parseInt(document.getElementById('max-price').value) || Infinity;
        
        // Get all checked categories
        const categoryCheckboxes = document.querySelectorAll('input[data-filter="category"]:checked');
        const selectedCategories = Array.from(categoryCheckboxes).map(cb => cb.value.toLowerCase());
        const allCategoriesSelected = selectedCategories.includes('') || selectedCategories.length === 0;
        
        // Get all checked brands
        const brandCheckboxes = document.querySelectorAll('input[data-filter="brand"]:checked');
        const selectedBrands = Array.from(brandCheckboxes).map(cb => cb.value.toLowerCase());
        const noBrandsSelected = selectedBrands.length === 0;
        
        // Get all checked ratings
        const ratingCheckboxes = document.querySelectorAll('input[data-filter="rating"]:checked');
        const selectedRatings = Array.from(ratingCheckboxes).map(cb => parseInt(cb.value));
        const noRatingsSelected = selectedRatings.length === 0;
        
        // Get all checked availability options
        const availabilityCheckboxes = document.querySelectorAll('input[data-filter="availability"]:checked');
        const selectedAvailability = Array.from(availabilityCheckboxes).map(cb => cb.value);
        const noAvailabilitySelected = selectedAvailability.length === 0;

        const filtered = products.filter(product => {
            const matchesPrice = product.price >= minPrice && product.price <= maxPrice;
            const matchesCategory = allCategoriesSelected || selectedCategories.includes(product.category);
            const matchesBrand = noBrandsSelected || selectedBrands.includes(product.brand);
            const matchesRating = noRatingsSelected || selectedRatings.includes(product.rating);
            const matchesAvailability = noAvailabilitySelected || selectedAvailability.includes(product.availability);
            
            return matchesPrice && matchesCategory && matchesBrand && matchesRating && matchesAvailability;
        });

        // Reset to page 1 when filters change
        currentPage = 1;
        
        renderProducts(filtered, currentPage);
        updateFilterCount(filtered.length);
        
        // Update counts for filter options based on current filter state
        updateFilterCounts(filtered);
    }

    // Update the count of filtered products
    function updateFilterCount(count) {
        const countsElement = document.querySelector('.filter-counts');
        if (countsElement) {
            if (count === products.length) {
                countsElement.textContent = `Wyświetlanie wszystkich produktów (${count})`;
            } else {
                countsElement.textContent = `Wyświetlanie ${count} z ${products.length} produktów`;
            }
        }
        
        // Update the products count below filter counts
        const productsCountElement = document.querySelector('.mb-6 > p.text-gray-600');
        if (productsCountElement) {
            const start = (currentPage - 1) * productsPerPage + 1;
            const end = Math.min(currentPage * productsPerPage, count);
            
            if (count > 0) {
                productsCountElement.textContent = `Wyświetlanie ${start}-${end} z ${count} produktów`;
            } else {
                productsCountElement.textContent = 'Brak produktów spełniających wybrane kryteria';
            }
        }
    }

    // Initialize price range slider
    function initRangeSlider() {
        const priceRange = document.getElementById('price-range');
        const minPrice = document.getElementById('min-price');
        const maxPrice = document.getElementById('max-price');
        
        if (priceRange && minPrice && maxPrice) {
            priceRange.addEventListener('input', function() {
                maxPrice.value = this.value;
            });
            
            minPrice.addEventListener('input', function() {
                if (parseInt(this.value) > parseInt(maxPrice.value)) {
                    this.value = maxPrice.value;
                }
            });
            
            maxPrice.addEventListener('input', function() {
                if (parseInt(this.value) < parseInt(minPrice.value)) {
                    this.value = minPrice.value;
                }
                priceRange.value = this.value;
            });
        }
    }

    // Setup checkbox filter event listeners
    function setupCheckboxFilters() {
        // Category checkboxes
        const categoryCheckboxes = document.querySelectorAll('input[data-filter="category"]');
        categoryCheckboxes.forEach(checkbox => {
            checkbox.addEventListener('change', function() {
                if (this.value === '' && this.checked) {
                    // If "All products" is checked, uncheck other categories
                    document.querySelectorAll('input[data-filter="category"]:not([value=""])').forEach(cb => {
                        cb.checked = false;
                    });
                } else if (this.value !== '' && this.checked) {
                    // If specific category is checked, uncheck "All products"
                    const allCategoriesCheckbox = document.querySelector('input[data-filter="category"][value=""]');
                    if (allCategoriesCheckbox) {
                        allCategoriesCheckbox.checked = false;
                    }
                }
            });
        });

        // All checkboxes
        const checkboxes = document.querySelectorAll('input[type="checkbox"][data-filter]');
        checkboxes.forEach(checkbox => {
            checkbox.addEventListener('change', filterProducts);
        });
    }

    // Setup sort functionality
    function setupSorting() {
        const sortSelect = document.getElementById('sort-select');
        if (sortSelect) {
            sortSelect.addEventListener('change', function() {
                const sortValue = this.value;
                let sortedProducts = [...filteredProducts];
                
                switch(sortValue) {
                    case 'price_asc':
                        sortedProducts.sort((a, b) => a.price - b.price);
                        break;
                    case 'price_desc':
                        sortedProducts.sort((a, b) => b.price - a.price);
                        break;
                    case 'rating':
                        sortedProducts.sort((a, b) => b.rating - a.rating);
                        break;
                    case 'popularity':
                        // This would usually be based on a popularity metric
                        // For now, we'll just randomize
                        sortedProducts.sort(() => Math.random() - 0.5);
                        break;
                    case 'newest':
                    default:
                        // Keep original order, which we assume is "newest"
                        break;
                }
                
                // Reset to page 1 when sorting changes
                currentPage = 1;
                
                renderProducts(sortedProducts, currentPage);
                updateFilterCount(sortedProducts.length);
            });
        }
    }

    // Clear all filters
    function clearFilters() {
        document.getElementById('min-price').value = 0;
        document.getElementById('max-price').value = 5000;
        document.getElementById('price-range').value = 5000;
        
        // Reset checkboxes
        document.querySelectorAll('input[type="checkbox"][data-filter]').forEach(cb => {
            cb.checked = false;
        });
        
        // Check "All products" checkbox
        const allProductsCheckbox = document.querySelector('input[data-filter="category"][value=""]');
        if (allProductsCheckbox) {
            allProductsCheckbox.checked = true;
        }
        
        // Check "In-stock" availability checkbox
        const inStockCheckbox = document.querySelector('input[data-filter="availability"][value="in-stock"]');
        if (inStockCheckbox) {
            inStockCheckbox.checked = true;
        }
        
        currentPage = 1;
        
        renderProducts(products, currentPage);
        updateFilterCount(products.length);
        updateFilterCounts();
    }

    // Setup the mobile filter toggle
    function setupMobileFilter() {
        const filterToggle = document.getElementById('filter-toggle');
        const filtersPanel = document.getElementById('filters-panel');
        
        if (filterToggle && filtersPanel) {
            filterToggle.addEventListener('click', function() {
                if (filtersPanel.classList.contains('hidden')) {
                    filtersPanel.classList.remove('hidden');
                } else {
                    filtersPanel.classList.add('hidden');
                }
            });
        }
    }

    // Setup Apply and Clear buttons
    function setupFilterButtons() {
        const applyBtn = document.getElementById('apply-filters');
        const clearBtn = document.getElementById('clear-filters');
        
        if (applyBtn) {
            applyBtn.addEventListener('click', filterProducts);
        }
        
        if (clearBtn) {
            clearBtn.addEventListener('click', clearFilters);
        }
    }

    // Initialize
    function init() {
        renderProducts(products, currentPage);
        updateFilterCounts();
        initRangeSlider();
        setupCheckboxFilters();
        setupSorting();
        setupMobileFilter();
        setupFilterButtons();
        updateFilterCount(products.length);
    }

    // Run initialization
    init();
}); 