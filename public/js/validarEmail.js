document.addEventListener("DOMContentLoaded", () => {

    const emailInput = document.getElementById("email");
    if (!emailInput) return;

    emailInput.addEventListener("keyup", async function () {
        let email = this.value.trim();

        if (email.length < 5) return;

        let response = await fetch(`/user/verificarEmail?email=` + encodeURIComponent(email));
        let data = await response.json();

        // 👀 ACÁ VA EL CONSOLE.LOG
        console.log("DATA:", data);

        let mensaje = document.getElementById("msgEmail");

        emailInput.classList.remove("is-invalid", "is-valid");

        if (data.existe) {
            mensaje.innerHTML = "Este email ya está registrado";
            mensaje.style.color = "red";
            emailInput.classList.add("is-invalid");
            emailInput.setCustomValidity("Email ya registrado");
        } else {
            mensaje.innerHTML = "Email disponible";
            mensaje.style.color = "green";
            emailInput.classList.add("is-valid");
            emailInput.setCustomValidity("");
        }
    });

});