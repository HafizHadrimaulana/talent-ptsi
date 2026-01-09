export async function getJSON(url) {
    const response = await fetch(url, {
        headers: { Accept: "application/json" },
    });
    if (!response.ok) {
        throw new Error(`GET ${url} failed with status ${response.status}`);
    }

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
    console.log("post form data", formData);
    const res = await fetch(url, {
        method: "POST",
        body: formData,
        headers: {
            "X-CSRF-TOKEN": document.querySelector('meta[name="csrf-token"]')
                .content,
        },
    });
    const data = await res.json();

    if (!res.ok) {
        const error = new Error(
            data.message || "Terjadi kesalahan pada server"
        );
        error.status = res.status;
        throw error;
    }

    return data;
}

export async function deleteJSON(url, options = {}) {
    const res = await fetch(url, {
        method: "DELETE",
        headers: {
            "X-CSRF-TOKEN": document.querySelector('meta[name="csrf-token"]')
                ?.content,
            Accept: "application/json",
        },
        ...options,
    });

    let data = null;

    try {
        data = await res.json();
    } catch (e) {
        // response bukan JSON
        data = null;
    }

    return {
        ok: res.ok, // true / false (HTTP level)
        statusCode: res.status, // 200, 404, 409, 500, dll
        data, // body response
    };
}
