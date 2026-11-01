// ==============================
// PET SERVICE DATA 
// ==============================
const services = [
    { id: 1, name: "Signature Dog Spa", category: "Dog", price: 85, imgSrc: "images/srv-dog-spa.jpg", description: "A complete cleansing, conditioning and coat care treatment for a fresh finish." },
    { id: 2, name: "Puppy First Groom", category: "Dog", price: 65, imgSrc: "images/srv-dog-puppy.jpg", description: "A gentle introduction to grooming designed to help puppies feel comfortable." },
    { id: 3, name: "Dog De-Shedding", category: "Dog", price: 95, imgSrc: "images/srv-dog-shed.jpg", description: "Specialised coat treatment that helps remove loose fur and reduce shedding." },
    { id: 4, name: "Dog Nail & Paw Care", category: "Dog", price: 35, imgSrc: "images/srv-dog-paw.jpg", description: "Professional nail trimming with gentle paw cleaning and moisturising." },
    { id: 5, name: "Gentle Cat Retreat", category: "Cat", price: 75, imgSrc: "images/srv-cat-spa.jpg", description: "A calm grooming experience designed especially for sensitive felines." },
    { id: 6, name: "Cat Coat & Brush", category: "Cat", price: 55, imgSrc: "images/srv-cat-brush.jpg", description: "Gentle brushing to reduce tangles and keep fur soft and healthy." },
    { id: 7, name: "Cat Hygiene Groom", category: "Cat", price: 65, imgSrc: "images/srv-cat-hygiene.jpg", description: "A hygienic session focused on maintaining cleanliness and everyday comfort." },
    { id: 8, name: "Cat Nail Care", category: "Cat", price: 30, imgSrc: "images/srv-cat-nail.jpg", description: "Safe and careful nail trimming performed in a pet-friendly environment." },
    { id: 9, name: "Bunny Care Session", category: "Rabbit", price: 55, imgSrc: "images/srv-rabbit-care.jpg", description: "Gentle coat care, hygiene maintenance and nail trimming for your rabbit." },
    { id: 10, name: "Rabbit Coat Groom", category: "Rabbit", price: 45, imgSrc: "images/srv-rabbit-groom.jpg", description: "Careful brushing to remove loose fur and maintain a clean coat." },
    { id: 11, name: "Rabbit Nail Trim", category: "Rabbit", price: 25, imgSrc: "images/srv-rabbit-nail.jpg", description: "Gentle nail trimming designed specifically for the safety of rabbits." },
    { id: 12, name: "Fluffy Treatment", category: "Rabbit", price: 60, imgSrc: "images/srv-rabbit-fluff.jpg", description: "Specialised coat maintenance for fluffy rabbits prone to matting." }
];

const servicesGrid = document.getElementById("servicesGrid");
const filterButtons = document.querySelectorAll(".filter-btn");

function displayServices(serviceList) {
    servicesGrid.style.opacity = 0;
    
    setTimeout(() => {
        servicesGrid.innerHTML = "";

        serviceList.forEach((service, index) => {
            const card = document.createElement("div");
            card.classList.add("service-card");
            card.style.animation = `fadeIn 0.4s ease forwards ${index * 0.05}s`;
            card.style.opacity = "0"; 

            card.innerHTML = `
                <div class="service-image" style="padding: 0; overflow: hidden; background: #e9e3d8;">
                    <img src="${service.imgSrc}" alt="${service.name}" style="width: 100%; height: 100%; object-fit: cover; transition: transform 0.3s ease;">
                </div>
                <div class="service-content">
                    <span class="category-tag">${service.category}</span>
                    <h3>${service.name}</h3>
                    <p>${service.description}</p>
                    <div class="service-bottom">
                        <span class="service-price">RM${service.price}</span>
                        <a href="booking.html">Book Now →</a>
                    </div>
                </div>
            `;
            servicesGrid.appendChild(card);
        });

        servicesGrid.style.opacity = 1;
        servicesGrid.style.transition = "opacity 0.3s ease";
    }, 200);
}

filterButtons.forEach(button => {
    button.addEventListener("click", () => {
        filterButtons.forEach(btn => btn.classList.remove("active"));
        button.classList.add("active");
        const category = button.dataset.category;
        if (category === "All") displayServices(services);
        else displayServices(services.filter(s => s.category === category));
    });
});

const style = document.createElement('style');
style.innerHTML = `@keyframes fadeIn { from { opacity: 0; transform: translateY(20px); } to { opacity: 1; transform: translateY(0); } }`;
document.head.appendChild(style);

displayServices(services);