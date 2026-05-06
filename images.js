uploadContainer = document.querySelector('.upload-container');

document.getElementById('file-upload').addEventListener('change', function() {
    var fileName = this.files[0] ? this.files[0].name : "Nincs fájl kiválasztva";
    document.getElementById('file-name').textContent = fileName;
});

if (sessionStorage.getItem("token")) {
    uploadContainer.style.display = "block";

}
else {
    uploadContainer.style.display = "none";
}

document.getElementById("uploadForm").addEventListener("submit", async (e) => {
    e.preventDefault();

    const fileInput = document.getElementById("file-upload");
    const token = sessionStorage.getItem("token");

    if (!token) {
        alert("be kell jelentkezned elotte!");
        return;
    }

    const formData = new FormData();
    formData.append('image', fileInput.files[0]);

    try {
        const response = await fetch('http://projekt.bzzyvc2.nhely.hu/api.php?type=file', {
            method: "POST",
            headers: {
                "Authorization": token
            },
            body: formData
        });

        const result = await response.json();

        if (response.ok) {
            alert("sikeres feltoltes");
        }
        else {
            alert("sikertelen feltoltes" + response.error);
        }
    }
    catch (err) {
        console.error(err);
        alert(err.message);
    }

});

async function loadImages() {
    try {
        // Sima GET kérés, nem kell token!
        const response = await fetch('http://projekt.bzzyvc2.nhely.hu/api.php?type=public_images');
        const images = await response.json();
        
        const container = document.getElementById('gallery');
        container.innerHTML = '';

        images.forEach(img => {
            const html = `
                <div class="image-card">
                    <p>${img.name}</p>
                    <img src="${img.url}" style="width: 200px; height: auto;">
                </div>
            `;
            container.innerHTML += html;
        });
    } catch (error) {
        console.error("Nem sikerült betölteni a képeket:", error);
    }
}

loadImages();