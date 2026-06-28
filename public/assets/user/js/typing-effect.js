const texts = ["ah ah ah", "GOAT Monic", "Joko Gaming", "Our Streamer Rara", "Rei is the best Valo Coach"];
const typingElement = document.getElementById("typing");
let textIndex = 0;

function showTypingEffect() {
    typingElement.textContent = texts[textIndex];
    typingElement.style.animation = "none";
    // Trigger reflow to restart animation
    void typingElement.offsetWidth;
    typingElement.style.animation = "typing 2s steps(30, end) forwards, typing-backwards 2s steps(30, end) 2s forwards";
    typingElement.style.animationDelay = "0s, 2s";

    // Ganti ke teks berikutnya setelah animasi selesai (4 detik)
    setTimeout(() => {
        textIndex = (textIndex + 1) % texts.length;
        showTypingEffect();
    }, 4000);
}

showTypingEffect();