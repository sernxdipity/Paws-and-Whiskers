// ==============================
// PET SERVICE DATA
// ==============================

const services = [

    // DOG SERVICES

    {
        id: 1,
        name: "Signature Dog Spa",
        category: "Dog",
        price: 85,
        image: "🐶",
        description:
            "A complete cleansing, conditioning and coat care treatment for a fresh and healthy finish."
    },

    {
        id: 2,
        name: "Puppy First Groom",
        category: "Dog",
        price: 65,
        image: "🐕",
        description:
            "A gentle introduction to grooming designed to help puppies feel safe and comfortable."
    },

    {
        id: 3,
        name: "Dog De-Shedding Treatment",
        category: "Dog",
        price: 95,
        image: "🐕‍🦺",
        description:
            "Specialised coat treatment that helps remove loose fur and reduce excessive shedding."
    },

    {
        id: 4,
        name: "Dog Nail & Paw Care",
        category: "Dog",
        price: 35,
        image: "🐾",
        description:
            "Professional nail trimming with gentle paw cleaning and moisturising treatment."
    },


    // CAT SERVICES

    {
        id: 5,
        name: "Gentle Cat Retreat",
        category: "Cat",
        price: 75,
        image: "🐱",
        description:
            "A calm grooming experience designed especially for sensitive feline companions."
    },

    {
        id: 6,
        name: "Cat Coat & Brush Care",
        category: "Cat",
        price: 55,
        image: "🐈",
        description:
            "Gentle brushing and coat maintenance to reduce tangles and keep fur soft and healthy."
    },

    {
        id: 7,
        name: "Cat Hygiene Groom",
        category: "Cat",
        price: 65,
        image: "🐈‍⬛",
        description:
            "A hygienic grooming session focused on maintaining cleanliness and everyday comfort."
    },

    {
        id: 8,
        name: "Cat Nail Care",
        category: "Cat",
        price: 30,
        image: "🐾",
        description:
            "Safe and careful nail trimming performed in a calm and pet-friendly environment."
    },


    // RABBIT SERVICES

    {
        id: 9,
        name: "Bunny Care Session",
        category: "Rabbit",
        price: 55,
        image: "🐰",
        description:
            "Gentle coat care, hygiene maintenance and nail trimming for your little companion."
    },

    {
        id: 10,
        name: "Rabbit Coat Grooming",
        category: "Rabbit",
        price: 45,
        image: "🐇",
        description:
            "Careful brushing to remove loose fur and help maintain a clean and comfortable coat."
    },

    {
        id: 11,
        name: "Rabbit Nail Trim",
        category: "Rabbit",
        price: 25,
        image: "🐾",
        description:
            "Gentle nail trimming designed specifically for the comfort and safety of rabbits."
    },

    {
        id: 12,
        name: "Fluffy Bunny Treatment",
        category: "Rabbit",
        price: 60,
        image: "🐰",
        description:
            "Specialised coat maintenance for fluffy and long-haired rabbits prone to matting."
    }

];

// Get the service grid from HTML
const servicesGrid =
    document.getElementById("servicesGrid");

// ==============================
// DISPLAY SERVICES
// ==============================

function displayServices(serviceList) {

    // Clear existing cards
    servicesGrid.innerHTML = "";


    // Create one card for every service
    serviceList.forEach(function (service) {

        const serviceCard =
            document.createElement("div");

        serviceCard.classList.add("service-card");


        serviceCard.innerHTML = `

            <div class="service-image">
                ${service.image}
            </div>

            <div class="service-content">

                <span class="category-tag">
                    ${service.category}
                </span>

                <h3>
                    ${service.name}
                </h3>

                <p>
                    ${service.description}
                </p>

                <div class="service-bottom">

                    <span class="service-price">
                        RM${service.price}
                    </span>

                    <a href="booking.html">
                        Book Now →
                    </a>

                </div>

            </div>

        `;


        servicesGrid.appendChild(serviceCard);

    });

}

// ==============================
// CATEGORY FILTER
// ==============================

const filterButtons =
    document.querySelectorAll(".filter-btn");


filterButtons.forEach(function (button) {

    button.addEventListener("click", function () {

        const selectedCategory =
            button.dataset.category;


        // Remove active class from all buttons
        filterButtons.forEach(function (btn) {

            btn.classList.remove("active");

        });


        // Highlight selected button
        button.classList.add("active");


        // Show all services
        if (selectedCategory === "All") {

            displayServices(services);

        }

        // Filter by pet category
        else {

            const filteredServices =
                services.filter(function (service) {

                    return service.category ===
                        selectedCategory;

                });


            displayServices(filteredServices);

        }

    });

});
displayServices(services);