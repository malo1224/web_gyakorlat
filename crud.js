document.addEventListener("DOMContentLoaded", function() {
    console.log("betoltodott mar az oldal");

    getData();

});

const baseUrl = "http://project.bzzyvc.nhely.hu/api.php?table=gp";

async function deleteRow(id) {
    const response = await fetch(`${baseUrl}&id=${id}`, {
        method: "DELETE",
        headers: {
            "Access-Control-Allow-Origin": "*"
        }
    });

    if (response.status === 200) {
        console.log("Sikeres törlés");
        await getData();
        return true;
    }
    else {
        console.error("Hiba történt a törlés során:", response);
        return false;
    }
}

function resetForm(prefix = "") {
    document.getElementById(`${prefix}${prefix ? "I" : "i"}d`).value = "";
    document.getElementById(`${prefix}${prefix ? "D" : "d"}atum`).value = "";
    document.getElementById(`${prefix}${prefix ? "N" : "n"}ev`).value = "";
    document.getElementById(`${prefix}${prefix ? "H" : "h"}elyszin`).value = "";
}

async function editPush(item) {
    document.getElementById("editId").value = item.id;
    document.getElementById("editDatum").value = item.datum;
    document.getElementById("editNev").value = item.nev;
    document.getElementById("editHelyszin").value = item.helyszin;
}

async function update() {
    const id = document.getElementById("editId").value;
    const datum = document.getElementById("editDatum").value;
    const nev = document.getElementById("editNev").value;
    const helyszin = document.getElementById("editHelyszin").value;

    const payload = {
        id,
        datum,
        nev,
        helyszin
    };

    const response = await fetch(baseUrl, {
        method: "PUT",
        headers: {
            "Access-Control-Allow-Origin": "*"
        },
        body: JSON.stringify(payload)
    });

    if (response.status === 200) {
        console.log("Sikeres update");
        await getData();
        return true;
    } else {
        console.error("Hiba történt az update során:", response);
        return false;
    }

}

async function create() {
    const datum = document.getElementById("datum").value;
    const nev = document.getElementById("nev").value;
    const helyszin = document.getElementById("helyszin").value;

    const payload = {
        datum,
        nev,
        helyszin
    };

    const response = await fetch(baseUrl, {
        method: "POST",
        headers: {
            "Access-Control-Allow-Origin": "*"
        },
        body: JSON.stringify(payload)
    });

    if (response.status === 200) {
        console.log("Sikeres mentés");
        await getData();
        return true;
    } else {
        console.error("Hiba történt a mentés során:", response);
        return false;
    }

}

function refreshTable(data) {
    
    const tBody = document.getElementById("tableBody");
    tBody.innerHTML = "";

    if (data) {
        console.log("Van adat");

        for (const item of data) {
            const tr = document.createElement("tr");
            const td1 = document.createElement("td");
            const td2 = document.createElement("td");
            const td3 = document.createElement("td");
            const td4 = document.createElement("td");

            const btnDiv = document.createElement("div");
            btnDiv.style.display = "flex";
            btnDiv.style.gap = "10px";

            const deleteButton = document.createElement("button");
            deleteButton.dataset.id = item.id;
            deleteButton.textContent = "Törlés";
            deleteButton.onclick = deleteRow.bind(deleteButton, item.id);
            deleteButton.style.backgroundColor = "#dc3545";

            const editButton = document.createElement("button");
            editButton.dataset.id = item.id;
            editButton.textContent = "Szerkesztés";
            editButton.onclick = editPush.bind(editButton, item);
            editButton.style.backgroundColor = "#007bff";

            btnDiv.appendChild(editButton);
            btnDiv.appendChild(deleteButton);
            
            td1.innerHTML = item.datum;
            td2.innerHTML = item.nev;
            td3.innerHTML = item.helyszin;
            td4.appendChild(btnDiv);

            tr.appendChild(td1);
            tr.appendChild(td2);
            tr.appendChild(td3);
            tr.appendChild(td4);

            tBody.appendChild(tr);
        }

    } else {
        console.log("Nincs adat.");
    }

}

async function getData() {
    const response = await fetch(baseUrl, {
        method: "GET",
        headers: {
            "Access-Control-Allow-Origin": "*"
        }
    });
    
    if (response.status === 200) {
        const content = await response.json();
        refreshTable(content);
    }
    else {
        console.error("Hiba történt az adatok lekérésekor:", response);
    }

}