function togglePass(id) {
    const input = document.getElementById(id);
    input.type = input.type === "password" ? "text" : "password";
}

// fuerza básica
function checkStrength(pass) {
    let score = 0;

    if (pass.length >= 8) score++;
    if (/[A-Z]/.test(pass)) score++;
    if (/[0-9]/.test(pass)) score++;
    if (/[^A-Za-z0-9]/.test(pass)) score++;

    return score;
}

document.getElementById("nueva").addEventListener("input", function () {
    const strength = checkStrength(this.value);
    const el = document.getElementById("strength");

    const levels = ["Muy débil", "Débil", "Media", "Fuerte", "Muy fuerte"];
    el.textContent = levels[strength] || "";
});

// comprobar coincidencia
function checkMatch() {
    const n1 = document.getElementById("nueva").value;
    const n2 = document.getElementById("nueva2").value;

    const el = document.getElementById("match");

    if (!n2) return;

    el.textContent = (n1 === n2) ? "✔ Coinciden" : "✖ No coinciden";
}

document.getElementById("nueva2").addEventListener("input", checkMatch);

// verificación AJAX contraseña actual
document.getElementById("actual").addEventListener("blur", async function () {
    const res = await fetch("verify_password.php", {
        method: "POST",
        headers: {"Content-Type": "application/x-www-form-urlencoded"},
        body: "actual=" + encodeURIComponent(this.value)
    });

    const data = await res.json();
    document.getElementById("actual-status").textContent =
        data.ok ? "✔ Correcta" : "✖ Incorrecta";
});