export function comprimirImagenCliente(file) {
    return new Promise((resolve, reject) => {

        const img = new Image();
        img.src = URL.createObjectURL(file);

        img.onload = () => {

            const canvas = document.createElement("canvas");
            const ctx = canvas.getContext("2d");

            const max = 1200;
            let w = img.width;
            let h = img.height;

            if (w > h && w > max) {
                h = h * max / w;
                w = max;
            } else if (h > max) {
                w = w * max / h;
                h = max;
            }

            canvas.width = w;
            canvas.height = h;
            ctx.drawImage(img, 0, 0, w, h);

            let quality = 0.8;
            let attempts = 0;

            function compress() {
                canvas.toBlob(b => {
                    if (!b) return reject();

                    if (b.size / (1024 * 1024) <= 6 || attempts >= 10) {
                        resolve(b);
                    } else {
                        attempts++;
                        quality *= 0.7;
                        compress();
                    }
                }, "image/jpeg", quality);
            }

            compress();
        };

        img.onerror = reject;
    });
}
