document.addEventListener("DOMContentLoaded", function () {
  const menuToggle = document.querySelector(".ri-menu-line");
  if (menuToggle) {
    menuToggle.addEventListener("click", function () {
      alert("Menu mobilne zostanie zaimplementowane");
    });
  }

  const filterToggle = document.getElementById("filter-toggle");
  const filtersPanel = document.getElementById("filters-panel");

  if (filterToggle && filtersPanel) {
    filterToggle.addEventListener("click", function () {
      if (filtersPanel.classList.contains("hidden")) {
        filtersPanel.classList.remove("hidden");
      } else {
        filtersPanel.classList.add("hidden");
      }
    });
  }

  const priceRange = document.getElementById("price-range");
  const minPrice = document.getElementById("min-price");
  const maxPrice = document.getElementById("max-price");

  if (priceRange && minPrice && maxPrice) {
    priceRange.addEventListener("input", function () {
      maxPrice.value = this.value;
    });

    minPrice.addEventListener("input", function () {
      if (parseInt(this.value) > parseInt(maxPrice.value)) {
        this.value = maxPrice.value;
      }
    });

    maxPrice.addEventListener("input", function () {
      if (parseInt(this.value) < parseInt(minPrice.value)) {
        this.value = minPrice.value;
      }
      priceRange.value = this.value;
    });
  }

  const wishlistButtons = document.querySelectorAll(
    ".group .absolute.top-3.right-3"
  );

  if (wishlistButtons) {
    wishlistButtons.forEach((button) => {
      button.addEventListener("click", function (e) {
        e.preventDefault();
        const icon = this.querySelector("i");
        if (icon.classList.contains("ri-heart-line")) {
          icon.classList.remove("ri-heart-line");
          icon.classList.add("ri-heart-fill");
          icon.classList.add("text-primary");
        } else {
          icon.classList.remove("ri-heart-fill");
          icon.classList.remove("text-primary");
          icon.classList.add("ri-heart-line");
        }
      });
    });
  }

  const paginationButtons = document.querySelectorAll(".pagination-btn");

  if (paginationButtons) {
    paginationButtons.forEach((button) => {
      if (!button.disabled) {
        button.addEventListener("click", function () {
          document.querySelectorAll(".pagination-btn").forEach((btn) => {
            btn.classList.remove("active");
          });
          this.classList.add("active");
        });
      }
    });
  }
});
