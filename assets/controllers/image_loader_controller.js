import { Controller } from "@hotwired/stimulus";

export default class extends Controller {
    static values = {
        token: String,
        imageUrl: String
    };

    async connect() {
        await this.loadImage();
    }

    async loadImage() {
        try {
            const response = await fetch(this.imageUrlValue, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded'
                }
            });

            if (!response.ok) {
                throw new Error('Failed to load image.');
            }

            const blob = await response.blob();
            const objectUrl = URL.createObjectURL(blob);

            this.element.src = objectUrl;
        } catch (error) {
            console.error(error);
        }
    }
}
