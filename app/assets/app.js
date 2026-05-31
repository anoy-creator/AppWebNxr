console.log("Symfony Encore JS chargé 🚀");

document.addEventListener("DOMContentLoaded", () => {
    const btn = document.querySelector("button");

    if (btn) {
        btn.addEventListener("click", () => {
            alert("Bouton cliqué !");
        });
    }
});
