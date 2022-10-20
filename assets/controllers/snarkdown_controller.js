import { Controller } from '@hotwired/stimulus';
import snarkdown from 'snarkdown';
const document = window.document;

/* stimulusFetch: 'lazy' */
export default class extends Controller {
    static targets = ['input'];

    outputElement = null;

    divTitle = document.createElement("label");

    initialize() {
        this.divTitle.innerText = " - output";
        this.divTitle.classList.add("form-control-label");
        this.divTitle.style.marginTop = "20px";
        this.divTitle.style.fontStyle = "italic";
        this.divTitle.style.color = "gray";
        this.outputElement = document.createElement('div');
        this.outputElement.className = 'markdown-preview';
        this.outputElement.textContent = 'MARKDOWN WILL BE RENDERED HERE';

        this.element.append(this.divTitle,this.outputElement);
    }

    connect() {
        this.render();
    }

    render() {
        const markdownContent = this.inputTarget.value;
        this.outputElement.innerHTML = snarkdown(markdownContent);
    }
}
