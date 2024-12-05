import { Controller } from "@hotwired/stimulus";

export default class extends Controller {
    static targets = ["loader"];

    show(event) {
        this.loaderTarget.classList.remove("hidden");
    }
}
