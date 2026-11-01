// ==============================
// TIME-AWARE GREETING
// ==============================
function setTimeAwareGreeting() {
    const greetingElement = document.getElementById("dynamicGreeting");
    if (!greetingElement) return;

    const hour = new Date().getHours();
    let timeGreeting = "Welcome";

    if (hour >= 5 && hour < 12) {
        timeGreeting = "Good Morning";
    } else if (hour >= 12 && hour < 18) {
        timeGreeting = "Good Afternoon";
    } else {
        timeGreeting = "Good Evening";
    }

    greetingElement.innerHTML = `${timeGreeting},<br>Premium Care for Your <span>Best Friend.</span>`;
}
setTimeAwareGreeting();

// ==============================
// PET GALLERY CAROUSEL 
// ==============================
const pets = [
    { imgSrc: "images/carousel-milo.jpg", name: "Meet Milo", description: "Fresh from his Signature Dog Spa!" },
    { imgSrc: "images/carousel-luna.jpg", name: "Meet Luna", description: "Looking beautiful after her Gentle Cat Retreat!" },
    { imgSrc: "images/carousel-mochi.jpg", name: "Meet Mochi", description: "Soft and fluffy after a Bunny Care Session!" },
    { imgSrc: "images/carousel-teddy.jpg", name: "Meet Teddy", description: "A fresh new look after his grooming session!" }
];

let currentPet = 0;

const carouselImageContainer = document.getElementById("carouselImage");
const carouselTitle = document.getElementById("carouselTitle");
const carouselDescription = document.getElementById("carouselDescription");
const nextBtn = document.getElementById("nextBtn");
const prevBtn = document.getElementById("prevBtn");
const carouselDots = document.getElementById("carouselDots");

pets.forEach((_, index) => {
    const dot = document.createElement("span");
    dot.classList.add("carousel-dot");
    dot.addEventListener("click", () => {
        currentPet = index;
        showPet(currentPet);
    });
    carouselDots.appendChild(dot);
});

function showPet(index) {
    carouselImageContainer.style.opacity = 0;
    
    setTimeout(() => {
        // Stop the emoji Preity :(
        carouselImageContainer.innerHTML = `<img src="${pets[index].imgSrc}" alt="${pets[index].name}" style="width: 100%; height: 100%; object-fit: cover;">`;
        carouselTitle.textContent = pets[index].name;
        carouselDescription.textContent = pets[index].description;
        
        document.querySelectorAll(".carousel-dot").forEach((dot, i) => {
            dot.classList.toggle("active", i === index);
        });

        carouselImageContainer.style.opacity = 1;
    }, 200);
}

nextBtn.addEventListener("click", () => {
    currentPet = (currentPet + 1) % pets.length;
    showPet(currentPet);
});

prevBtn.addEventListener("click", () => {
    currentPet = (currentPet - 1 + pets.length) % pets.length;
    showPet(currentPet);
});

showPet(currentPet);

setInterval(() => {
    currentPet = (currentPet + 1) % pets.length;
    showPet(currentPet);
}, 5000);