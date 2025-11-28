export async function getJSON(url) {
    const response = await fetch(url, { headers: { Accept: "application/json" } });
    if (!response.ok) {
        throw new Error(`GET ${url} failed with status ${response.status}`);
    }

    console.log("response", response);

    const data = await response.json();
    return data;
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

    if (!data.ok) {
        throw new Error(`Request gagal: ${data.status}`);
    }

    const response = await data.json();

    return response;
}

export async function postFormData(url, formData) {
    console.log('post form data', formData);
    const res = await fetch(url, {
        method: "POST",
        body: formData,
        headers: {
            "X-CSRF-TOKEN": document.querySelector('meta[name="csrf-token"]').content,
        },
    });
    console.log('res in post form data', res);
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
