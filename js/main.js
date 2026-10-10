// ==============================
// PET GALLERY CAROUSEL
// ==============================


// Information for each pet
const pets = [
    {
        emoji: "🐶",
        name: "Meet Milo",
        description: "Fresh from his Signature Dog Spa!"
    },

    {
        emoji: "🐱",
        name: "Meet Luna",
        description: "Looking beautiful after her Gentle Cat Retreat!"
    },

    {
        emoji: "🐰",
        name: "Meet Mochi",
        description: "Soft and fluffy after a Bunny Care Session!"
    },

    {
        emoji: "🐕",
        name: "Meet Teddy",
        description: "A fresh new look after his grooming session!"
    }
];


// Start with the first pet
let currentPet = 0;


// Get HTML elements
const carouselImage = document.getElementById("carouselImage");
const carouselTitle = document.getElementById("carouselTitle");
const carouselDescription = document.getElementById("carouselDescription");

const nextBtn = document.getElementById("nextBtn");
const prevBtn = document.getElementById("prevBtn");

const carouselDots = document.getElementById("carouselDots");

// Display the selected pet
function showPet(index) {

    carouselImage.textContent = pets[index].emoji;

    carouselTitle.textContent = pets[index].name;

    carouselDescription.textContent =
        pets[index].description;


    updateDots();
}

nextBtn.addEventListener("click", function () {

    currentPet++;

    if (currentPet >= pets.length) {
        currentPet = 0;
    }

    showPet(currentPet);

});

prevBtn.addEventListener("click", function () {

    currentPet--;

    if (currentPet < 0) {
        currentPet = pets.length - 1;
    }

    showPet(currentPet);

});

// Create carousel dots
pets.forEach(function (pet, index) {

    const dot = document.createElement("span");

    dot.classList.add("carousel-dot");


    dot.addEventListener("click", function () {

        currentPet = index;

        showPet(currentPet);

    });


    carouselDots.appendChild(dot);

});

function updateDots() {

    const dots =
        document.querySelectorAll(".carousel-dot");


    dots.forEach(function (dot, index) {

        if (index === currentPet) {

            dot.classList.add("active");

        } else {

            dot.classList.remove("active");

        }

    });

}

showPet(currentPet);

setInterval(function () {

    currentPet++;

    if (currentPet >= pets.length) {
        currentPet = 0;
    }

    showPet(currentPet);

}, 4000);