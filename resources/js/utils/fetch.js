export async function getJSON(url) {
    const response = await fetch(url);
    if (!response.ok) throw new Error("Fetch failed");
    return await response.json();
}

export async function postJSON(url, formData = null) {
    const token = document.querySelector('meta[name="csrf-token"]').content;

    const options = {
        method: "POST",
        headers: { "X-CSRF-TOKEN": token },
    };

    if (formData) {
        options.body = formData;
    }

    const data = await fetch(url, options);
    console.log("data fetch", data);

    if (!data.ok) {
        throw new Error(`Request gagal: ${data.status}`);
    }

    const response = await data.json();
    console.log("resp on fetch", response);

    return response;
}

export async function postFormData(url, formData) {
    console.log('form data', formData)
    const res = await fetch(url, {
        method: "POST",
        body: formData,
        headers: {
            "X-CSRF-TOKEN": document.querySelector('meta[name="csrf-token"]').content,
        },
    });
    console.log('res in fetch', res)
    return res.json();
}

export async function deleteJSON(url) {
    const res = await fetch(url, {
        method: "DELETE",
        headers: {
            "X-CSRF-TOKEN": document.querySelector('meta[name="csrf-token"]')
                .content,
            Accept: "application/json",
        },
    });

    if (!res.ok) throw new Error("Gagal menghapus data");
    return res.json();
}
