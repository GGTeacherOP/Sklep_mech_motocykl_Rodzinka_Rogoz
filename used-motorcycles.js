document.addEventListener('DOMContentLoaded', function() {
    // Sample data for motorcycles
    const motorcycles = [
        {
            id: 1,
            make: 'Honda',
            model: 'CBR 600RR',
            year: 2023,
            price: 39900,
            mileage: 0,
            image: 'https://readdy.ai/api/search-image?query=Honda%20CBR%20600RR%20motorcycle%20on%20white%20background%20professional%20photography&width=400&height=300&seq=1',
            condition: 'New'
        },
        {
            id: 2,
            make: 'Yamaha',
            model: 'MT-09',
            year: 2020,
            price: 42500,
            mileage: 8750,
            image: 'https://readdy.ai/api/search-image?query=Yamaha%20MT-09%20motorcycle%20on%20white%20background%20professional%20photography&width=400&height=300&seq=2',
            condition: 'Used'
        },
        {
            id: 3,
            make: 'Kawasaki',
            model: 'Ninja ZX-10R',
            year: 2018,
            price: 45999,
            mileage: 15200,
            image: 'https://readdy.ai/api/search-image?query=Kawasaki%20Ninja%20ZX-10R%20motorcycle%20on%20white%20background%20professional%20photography&width=400&height=300&seq=3',
            condition: 'Used'
        },
        {
            id: 4,
            make: 'BMW',
            model: 'R 1250 GS',
            year: 2023,
            price: 89900,
            mileage: 0,
            image: 'https://readdy.ai/api/search-image?query=BMW%20R%201250%20GS%20motorcycle%20on%20white%20background%20professional%20photography&width=400&height=300&seq=4',
            condition: 'New'
        },
        {
            id: 5,
            make: 'Suzuki',
            model: 'GSX-R1000',
            year: 2019,
            price: 49900,
            mileage: 11000,
            image: 'https://readdy.ai/api/search-image?query=Suzuki%20GSX-R1000%20motorcycle%20on%20white%20background%20professional%20photography&width=400&height=300&seq=5',
            condition: 'Used'
        },
        {
            id: 6,
            make: 'Ducati',
            model: 'Panigale V4',
            year: 2020,
            price: 105000,
            mileage: 3200,
            image: 'https://readdy.ai/api/search-image?query=Ducati%20Panigale%20V4%20motorcycle%20on%20white%20background%20professional%20photography&width=400&height=300&seq=6',
            condition: 'Used'
        },
        {
            id: 7,
            make: 'Triumph',
            model: 'Street Triple',
            year: 2021,
            price: 38500,
            mileage: 4800,
            image: 'https://readdy.ai/api/search-image?query=Triumph%20Street%20Triple%20motorcycle%20on%20white%20background%20professional%20photography&width=400&height=300&seq=7',
            condition: 'Used'
        },
        {
            id: 8,
            make: 'KTM',
            model: '1290 Super Duke R',
            year: 2022,
            price: 75000,
            mileage: 2100,
            image: 'https://readdy.ai/api/search-image?query=KTM%201290%20Super%20Duke%20R%20motorcycle%20on%20white%20background%20professional%20photography&width=400&height=300&seq=8',
            condition: 'Used'
        },
        {
            id: 9,
            make: 'Harley-Davidson',
            model: 'Street Glide',
            year: 2019,
            price: 112000,
            mileage: 18500,
            image: 'https://readdy.ai/api/search-image?query=Harley-Davidson%20Street%20Glide%20motorcycle%20on%20white%20background%20professional%20photography&width=400&height=300&seq=9',
            condition: 'Used'
        },
        {
            id: 10,
            make: 'Aprilia',
            model: 'RSV4 Factory',
            year: 2021,
            price: 79900,
            mileage: 5600,
            image: 'https://readdy.ai/api/search-image?query=Aprilia%20RSV4%20Factory%20motorcycle%20on%20white%20background%20professional%20photography&width=400&height=300&seq=10',
            condition: 'Used'
        },
        {
            id: 11,
            make: 'Honda',
            model: 'Africa Twin',
            year: 2020,
            price: 58500,
            mileage: 12800,
            image: 'https://readdy.ai/api/search-image?query=Honda%20Africa%20Twin%20motorcycle%20on%20white%20background%20professional%20photography&width=400&height=300&seq=11',
            condition: 'Used'
        },
        {
            id: 12,
            make: 'Yamaha',
            model: 'Tracer 900 GT',
            year: 2021,
            price: 45900,
            mileage: 8200,
            image: 'https://readdy.ai/api/search-image?query=Yamaha%20Tracer%20900%20GT%20motorcycle%20on%20white%20background%20professional%20photography&width=400&height=300&seq=12',
            condition: 'Used'
        },
        {
            id: 13,
            make: 'Kawasaki',
            model: 'Z900',
            year: 2022,
            price: 39500,
            mileage: 3400,
            image: 'https://readdy.ai/api/search-image?query=Kawasaki%20Z900%20motorcycle%20on%20white%20background%20professional%20photography&width=400&height=300&seq=13',
            condition: 'Used'
        },
        {
            id: 14,
            make: 'Ducati',
            model: 'Monster 937',
            year: 2023,
            price: 55000,
            mileage: 800,
            image: 'https://readdy.ai/api/search-image?query=Ducati%20Monster%20937%20motorcycle%20on%20white%20background%20professional%20photography&width=400&height=300&seq=14',
            condition: 'Used'
        },
        {
            id: 15,
            make: 'BMW',
            model: 'S1000RR',
            year: 2022,
            price: 82500,
            mileage: 4200,
            image: 'https://readdy.ai/api/search-image?query=BMW%20S1000RR%20motorcycle%20on%20white%20background%20professional%20photography&width=400&height=300&seq=15',
            condition: 'Used'
        },
        {
            id: 16,
            make: 'KTM',
            model: '390 Duke',
            year: 2021,
            price: 22500,
            mileage: 5600,
            image: 'https://readdy.ai/api/search-image?query=KTM%20390%20Duke%20motorcycle%20on%20white%20background%20professional%20photography&width=400&height=300&seq=16',
            condition: 'Used'
        },
        {
            id: 17,
            make: 'Suzuki',
            model: 'V-Strom 650',
            year: 2020,
            price: 35900,
            mileage: 14200,
            image: 'https://readdy.ai/api/search-image?query=Suzuki%20V-Strom%20650%20motorcycle%20on%20white%20background%20professional%20photography&width=400&height=300&seq=17',
            condition: 'Used'
        },
        {
            id: 18,
            make: 'Triumph',
            model: 'Bonneville T120',
            year: 2019,
            price: 42000,
            mileage: 9800,
            image: 'https://readdy.ai/api/search-image?query=Triumph%20Bonneville%20T120%20motorcycle%20on%20white%20background%20professional%20photography&width=400&height=300&seq=18',
            condition: 'Used'
        }
    ];

    // Function to render motorcycle cards
    function renderMotorcycles(motorcycles) {
        const grid = document.getElementById('motorcyclesGrid');
        grid.innerHTML = '';
        
        motorcycles.forEach(motorcycle => {
            const card = createMotorcycleCard(motorcycle);
            grid.appendChild(card);
        });
    }

    // Function to create a motorcycle card
    function createMotorcycleCard(motorcycle) {
        const card = document.createElement('div');
        card.className = 'bg-white rounded-lg shadow-sm overflow-hidden hover:shadow-md transition group';
        
        // Determine the badge color based on condition
        const badgeClass = motorcycle.condition === 'New' ? 'bg-green-500' : 'bg-primary';
        
        card.innerHTML = `
            <div class="relative">
                <img src="${motorcycle.image}" alt="${motorcycle.make} ${motorcycle.model}" class="w-full h-60 object-cover object-center">
                <span class="absolute top-3 left-3 ${badgeClass} text-white text-xs font-medium px-2 py-1 rounded">${motorcycle.condition}</span>
                <button class="absolute top-3 right-3 w-8 h-8 flex items-center justify-center bg-white rounded-full text-gray-400 hover:text-primary transition opacity-0 group-hover:opacity-100">
                    <i class="ri-heart-line"></i>
                </button>
            </div>
            <div class="p-4">
                <h3 class="font-medium text-gray-900 mb-1">${motorcycle.make} ${motorcycle.model}</h3>
                <p class="text-gray-500 text-sm mb-2">Rok: ${motorcycle.year} | Przebieg: ${formatNumber(motorcycle.mileage)} km</p>
                <div class="flex justify-between items-center">
                    <span class="text-lg font-bold text-gray-900">${formatPrice(motorcycle.price)} zł</span>
                    <a href="#" class="bg-primary text-white px-4 py-2 rounded-button text-sm font-medium hover:bg-opacity-90 transition">
                        Szczegóły
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

    // Helper function to format numbers (for mileage)
    function formatNumber(num) {
        return num.toString().replace(/\B(?=(\d{3})+(?!\d))/g, " ");
    }

    // Filter functions
    function filterMotorcycles() {
        const minPrice = parseInt(document.getElementById('minPrice').value) || 0;
        const maxPrice = parseInt(document.getElementById('maxPrice').value) || Infinity;
        const minYear = parseInt(document.getElementById('minYear').value) || 0;
        const maxYear = parseInt(document.getElementById('maxYear').value) || Infinity;
        const maxMileage = parseInt(document.getElementById('maxMileage').value) || Infinity;
        
        // Get all checked makes
        const makeCheckboxes = document.querySelectorAll('input[data-filter="make"]:checked');
        const selectedMakes = Array.from(makeCheckboxes).map(cb => cb.value.toLowerCase());
        const allMakesSelected = selectedMakes.includes('') || selectedMakes.length === 0;
        
        // Get all checked conditions
        const conditionCheckboxes = document.querySelectorAll('input[data-filter="condition"]:checked');
        const selectedConditions = Array.from(conditionCheckboxes).map(cb => cb.value.toLowerCase());
        const noConditionsSelected = selectedConditions.length === 0;

        const filtered = motorcycles.filter(motorcycle => {
            const matchesPrice = motorcycle.price >= minPrice && motorcycle.price <= maxPrice;
            const matchesYear = motorcycle.year >= minYear && motorcycle.year <= maxYear;
            const matchesMileage = motorcycle.mileage <= maxMileage;
            const matchesMake = allMakesSelected || selectedMakes.includes(motorcycle.make.toLowerCase());
            const matchesCondition = noConditionsSelected || selectedConditions.includes(motorcycle.condition.toLowerCase());
            
            return matchesPrice && matchesYear && matchesMileage && matchesMake && matchesCondition;
        });

        renderMotorcycles(filtered);
        updateFilterCount(filtered.length);
    }

    // Update the count of filtered motorcycles
    function updateFilterCount(count) {
        const countsElement = document.querySelector('.filter-counts');
        if (countsElement) {
            countsElement.textContent = `Wyświetlanie ${count} motocykli`;
        }
    }

    // Initialize price range slider
    function initRangeSliders() {
        const priceRange = document.getElementById('price-range');
        const minPrice = document.getElementById('minPrice');
        const maxPrice = document.getElementById('maxPrice');
        
        if (priceRange && minPrice && maxPrice) {
            priceRange.addEventListener('input', function() {
                maxPrice.value = this.value;
                filterMotorcycles();
            });
            
            minPrice.addEventListener('input', function() {
                filterMotorcycles();
            });
            
            maxPrice.addEventListener('input', function() {
                priceRange.value = this.value;
                filterMotorcycles();
            });
        }
        
        const mileageRange = document.getElementById('mileage-range');
        const maxMileage = document.getElementById('maxMileage');
        
        if (mileageRange && maxMileage) {
            mileageRange.addEventListener('input', function() {
                maxMileage.value = this.value;
                filterMotorcycles();
            });
            
            maxMileage.addEventListener('input', function() {
                mileageRange.value = this.value;
                filterMotorcycles();
            });
        }
    }

    // Setup checkbox filter event listeners
    function setupCheckboxFilters() {
        const checkboxes = document.querySelectorAll('input[type="checkbox"][data-filter]');
        checkboxes.forEach(checkbox => {
            checkbox.addEventListener('change', function() {
                if (this.dataset.filter === 'make' && this.value === '') {
                    // If "All makes" is checked, uncheck other makes
                    if (this.checked) {
                        document.querySelectorAll('input[data-filter="make"]:not([value=""])').forEach(cb => {
                            cb.checked = false;
                        });
                    }
                } else if (this.dataset.filter === 'make' && this.value !== '') {
                    // If specific make is checked, uncheck "All makes"
                    if (this.checked) {
                        const allMakesCheckbox = document.querySelector('input[data-filter="make"][value=""]');
                        if (allMakesCheckbox) {
                            allMakesCheckbox.checked = false;
                        }
                    }
                }
                filterMotorcycles();
            });
        });
    }

    // Clear all filters
    function clearFilters() {
        document.getElementById('minPrice').value = 0;
        document.getElementById('maxPrice').value = 150000;
        document.getElementById('price-range').value = 150000;
        
        document.getElementById('minYear').value = 1990;
        document.getElementById('maxYear').value = 2025;
        
        document.getElementById('maxMileage').value = 50000;
        document.getElementById('mileage-range').value = 50000;
        
        // Reset checkboxes
        document.querySelectorAll('input[type="checkbox"][data-filter]').forEach(cb => {
            cb.checked = false;
        });
        
        // Check "All makes" checkbox
        const allMakesCheckbox = document.querySelector('input[data-filter="make"][value=""]');
        if (allMakesCheckbox) {
            allMakesCheckbox.checked = true;
        }
        
        filterMotorcycles();
    }

    // Setup the mobile filter toggle
    function setupMobileFilter() {
        const filterToggle = document.getElementById('filter-toggle');
        const filtersPanel = document.getElementById('filters-panel');
        
        if (filterToggle && filtersPanel) {
            filterToggle.addEventListener('click', function() {
                filtersPanel.classList.toggle('hidden');
                filtersPanel.classList.toggle('lg:block');
            });
        }
    }

    // Add event listeners to input fields
    document.getElementById('minYear').addEventListener('input', filterMotorcycles);
    document.getElementById('maxYear').addEventListener('input', filterMotorcycles);

    // Setup the apply and clear buttons
    const applyButton = document.getElementById('apply-filters');
    if (applyButton) {
        applyButton.addEventListener('click', filterMotorcycles);
    }
    
    const clearButton = document.getElementById('clear-filters');
    if (clearButton) {
        clearButton.addEventListener('click', clearFilters);
    }

    // Initialize
    setupMobileFilter();
    initRangeSliders();
    setupCheckboxFilters();
    renderMotorcycles(motorcycles);
    updateFilterCount(motorcycles.length);

    // Sortowanie
    const sortSelect = document.querySelector('select');
    if (sortSelect) {
        sortSelect.addEventListener('change', function() {
            const sortValue = this.value;
            let sortedMotorcycles = [...motorcycles];

            switch(sortValue) {
                case 'newest':
                    sortedMotorcycles.sort((a, b) => b.id - a.id);
                    break;
                case 'price_asc':
                    sortedMotorcycles.sort((a, b) => a.price - b.price);
                    break;
                case 'price_desc':
                    sortedMotorcycles.sort((a, b) => b.price - a.price);
                    break;
                case 'year_desc':
                    sortedMotorcycles.sort((a, b) => b.year - a.year);
                    break;
                case 'mileage_asc':
                    sortedMotorcycles.sort((a, b) => a.mileage - b.mileage);
                    break;
            }

            renderMotorcycles(sortedMotorcycles);
        });
    }
});
