// DOM Elements
const statusTimeEl = document.querySelector(".status-time")
const themeToggleBtn = document.querySelector(".theme-toggle")
const bannerSlider = document.querySelector(".banner-slider")
const banners = document.querySelectorAll(".banner")
const indicators = document.querySelectorAll(".banner-indicators .indicator")
const favoriteButtons = document.querySelectorAll(".favorite-btn")
const galleryThumbs = document.querySelectorAll(".thumb")
const galleryMain = document.querySelector(".gallery-main img")
const paymentTabs = document.querySelectorAll(".payment-tab")
const paymentDetails = document.querySelectorAll(".payment-details")
const orderModal = document.querySelector(".order-modal")
const orderForm = document.querySelector(".order-form")
const closeModalBtn = document.querySelector(".close-modal")
const buyNowBtn = document.querySelector(".buy-now-btn")
const fullscreenGallery = document.querySelector(".fullscreen-gallery")
const galleryClose = document.querySelector(".gallery-close")
const galleryImage = document.querySelector(".gallery-image")
const galleryPrev = document.querySelector(".gallery-prev")
const galleryNext = document.querySelector(".gallery-next")
const galleryThumbnails = document.querySelectorAll(".gallery-thumb")

// Update time in status bar
function updateTime() {
  const now = new Date()
  let hours = now.getHours()
  let minutes = now.getMinutes()

  // Format hours and minutes to always have 2 digits
  hours = hours < 10 ? "0" + hours : hours
  minutes = minutes < 10 ? "0" + minutes : minutes

  if (statusTimeEl) {
    statusTimeEl.textContent = `${hours}:${minutes}`
  }
}

// Initialize time and update every minute
updateTime()
setInterval(updateTime, 60000)

// Theme Toggle
if (themeToggleBtn) {
  themeToggleBtn.addEventListener("click", () => {
    document.body.classList.toggle("dark-mode")

    if (document.body.classList.contains("dark-mode")) {
      themeToggleBtn.innerHTML = '<i class="fas fa-sun"></i>'
      localStorage.setItem("theme", "dark")
    } else {
      themeToggleBtn.innerHTML = '<i class="fas fa-moon"></i>'
      localStorage.setItem("theme", "light")
    }
  })
}

// Check for saved theme preference
const savedTheme = localStorage.getItem("theme")
if (savedTheme === "dark") {
  document.body.classList.add("dark-mode")
  if (themeToggleBtn) {
    themeToggleBtn.innerHTML = '<i class="fas fa-sun"></i>'
  }
}

// Banner Slider
let currentBanner = 0
const bannerCount = banners.length

function showBanner(index) {
  // Hide all banners
  banners.forEach((banner) => {
    banner.classList.remove("active")
  })

  // Deactivate all indicators
  indicators.forEach((indicator) => {
    indicator.classList.remove("active")
  })

  // Show the selected banner and activate its indicator
  if (banners[index]) {
    banners[index].classList.add("active")
  }

  if (indicators[index]) {
    indicators[index].classList.add("active")
  }

  currentBanner = index
}

function nextBanner() {
  let nextIndex = currentBanner + 1
  if (nextIndex >= bannerCount) {
    nextIndex = 0
  }
  showBanner(nextIndex)
}

// Initialize banner slider
if (bannerSlider) {
  // Set up automatic sliding
  const bannerInterval = setInterval(nextBanner, 5000)

  // Add click event to indicators
  indicators.forEach((indicator, index) => {
    indicator.addEventListener("click", () => {
      clearInterval(bannerInterval)
      showBanner(index)
    })
  })
}

// Favorite Buttons
favoriteButtons.forEach((btn) => {
  btn.addEventListener("click", (e) => {
    e.preventDefault()
    btn.classList.toggle("active")

    if (btn.classList.contains("active")) {
      btn.innerHTML = '<i class="fas fa-heart"></i>'
    } else {
      btn.innerHTML = '<i class="far fa-heart"></i>'
    }
  })
})

// Product Gallery
if (galleryThumbs.length > 0 && galleryMain) {
  galleryThumbs.forEach((thumb) => {
    thumb.addEventListener("click", () => {
      // Remove active class from all thumbs
      galleryThumbs.forEach((t) => t.classList.remove("active"))

      // Add active class to clicked thumb
      thumb.classList.add("active")

      // Update main image
      const imgSrc = thumb.querySelector("img").getAttribute("src")

      // Add fade effect
      galleryMain.classList.add("image-fade")

      setTimeout(() => {
        galleryMain.setAttribute("src", imgSrc)
        galleryMain.classList.remove("image-fade")
      }, 300)
    })
  })
}

// Payment Tabs
if (paymentTabs.length > 0) {
  paymentTabs.forEach((tab, index) => {
    tab.addEventListener("click", () => {
      // Remove active class from all tabs and details
      paymentTabs.forEach((t) => t.classList.remove("active"))
      paymentDetails.forEach((d) => d.classList.remove("active"))

      // Add active class to clicked tab and corresponding details
      tab.classList.add("active")
      if (paymentDetails[index]) {
        paymentDetails[index].classList.add("active")
      }
    })
  })
}

// Order Modal
if (buyNowBtn && orderModal) {
  buyNowBtn.addEventListener("click", () => {
    orderModal.classList.add("active")
    document.body.style.overflow = "hidden"
  })
}

if (closeModalBtn && orderModal) {
  closeModalBtn.addEventListener("click", () => {
    orderModal.classList.remove("active")
    document.body.style.overflow = ""
  })

  // Close modal when clicking outside the content
  orderModal.addEventListener("click", (e) => {
    if (e.target === orderModal) {
      orderModal.classList.remove("active")
      document.body.style.overflow = ""
    }
  })
}

// Fullscreen Gallery
if (galleryMain && fullscreenGallery) {
  galleryMain.addEventListener("click", () => {
    const imgSrc = galleryMain.getAttribute("src")
    if (galleryImage) {
      galleryImage.setAttribute("src", imgSrc)
    }
    fullscreenGallery.classList.add("active")
    document.body.style.overflow = "hidden"
  })
}

if (galleryClose && fullscreenGallery) {
  galleryClose.addEventListener("click", () => {
    fullscreenGallery.classList.remove("active")
    document.body.style.overflow = ""
  })
}

// Gallery Navigation
let currentGalleryIndex = 0
const galleryImages = Array.from(galleryThumbs).map((thumb) => thumb.querySelector("img").getAttribute("src"))

if (galleryPrev && galleryNext && galleryImage) {
  galleryPrev.addEventListener("click", () => {
    currentGalleryIndex = (currentGalleryIndex - 1 + galleryImages.length) % galleryImages.length
    galleryImage.setAttribute("src", galleryImages[currentGalleryIndex])
    updateGalleryThumbnails()
  })

  galleryNext.addEventListener("click", () => {
    currentGalleryIndex = (currentGalleryIndex + 1) % galleryImages.length
    galleryImage.setAttribute("src", galleryImages[currentGalleryIndex])
    updateGalleryThumbnails()
  })
}

// Update gallery thumbnails
function updateGalleryThumbnails() {
  galleryThumbnails.forEach((thumb, index) => {
    if (index === currentGalleryIndex) {
      thumb.classList.add("active")
    } else {
      thumb.classList.remove("active")
    }
  })
}

// Gallery Thumbnails
if (galleryThumbnails.length > 0) {
  galleryThumbnails.forEach((thumb, index) => {
    thumb.addEventListener("click", () => {
      currentGalleryIndex = index
      galleryImage.setAttribute("src", galleryImages[index])
      updateGalleryThumbnails()
    })
  })
}

// Helper Functions
function formatPrice(price) {
  return price.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ",")
}

function formatDate(date) {
  const options = { year: "numeric", month: "long", day: "numeric" }
  return new Date(date).toLocaleDateString("uz-UZ", options)
}

// Initialize any countdown timers
const countdownElements = document.querySelectorAll(".countdown-timer")
if (countdownElements.length > 0) {
  countdownElements.forEach((countdown) => {
    const hoursEl = countdown.querySelector(".hours")
    const minutesEl = countdown.querySelector(".minutes")
    const secondsEl = countdown.querySelector(".seconds")

    if (hoursEl && minutesEl && secondsEl) {
      let hours = Number.parseInt(hoursEl.textContent)
      let minutes = Number.parseInt(minutesEl.textContent)
      let seconds = Number.parseInt(secondsEl.textContent)

      const countdownInterval = setInterval(() => {
        seconds--

        if (seconds < 0) {
          seconds = 59
          minutes--

          if (minutes < 0) {
            minutes = 59
            hours--

            if (hours < 0) {
              clearInterval(countdownInterval)
              hours = 0
              minutes = 0
              seconds = 0
            }
          }
        }

        hoursEl.textContent = hours < 10 ? "0" + hours : hours
        minutesEl.textContent = minutes < 10 ? "0" + minutes : minutes
        secondsEl.textContent = seconds < 10 ? "0" + seconds : seconds
      }, 1000)
    }
  })
}

// Responsive adjustments
function handleResponsiveLayout() {
  const windowWidth = window.innerWidth

  // Adjust product grid for different screen sizes
  const productsGrid = document.querySelector(".products-grid")
  if (productsGrid) {
    if (windowWidth < 576) {
      productsGrid.style.gridTemplateColumns = "repeat(2, 1fr)"
    } else if (windowWidth < 992) {
      productsGrid.style.gridTemplateColumns = "repeat(3, 1fr)"
    } else if (windowWidth < 1200) {
      productsGrid.style.gridTemplateColumns = "repeat(4, 1fr)"
    } else {
      productsGrid.style.gridTemplateColumns = "repeat(5, 1fr)"
    }
  }
}

// Run responsive adjustments on load and resize
window.addEventListener("load", handleResponsiveLayout)
window.addEventListener("resize", handleResponsiveLayout)

// Lazy loading images
document.addEventListener("DOMContentLoaded", () => {
  const lazyImages = document.querySelectorAll("img[data-src]")

  if ("IntersectionObserver" in window) {
    const imageObserver = new IntersectionObserver((entries, observer) => {
      entries.forEach((entry) => {
        if (entry.isIntersecting) {
          const img = entry.target
          img.src = img.dataset.src
          img.removeAttribute("data-src")
          imageObserver.unobserve(img)
        }
      })
    })

    lazyImages.forEach((img) => {
      imageObserver.observe(img)
    })
  } else {
    // Fallback for browsers that don't support IntersectionObserver
    lazyImages.forEach((img) => {
      img.src = img.dataset.src
      img.removeAttribute("data-src")
    })
  }
})
