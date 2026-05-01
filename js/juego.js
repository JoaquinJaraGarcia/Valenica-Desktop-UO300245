class JuegoTrivial {
    constructor() {
        this.questions = [
            { q: "¿Cuál es el parque natural famoso cerca de Valencia que ofrecemos en nuestras rutas?", o: ["Doñana", "Albufera", "Picos de Europa", "Teide", "Garajonay"], a: 1 },
            { q: "¿Qué plato típico es nuestra especialidad en los tours gastronómicos?", o: ["Cocido", "Gazpacho", "Paella valenciana", "Fabada", "Tortilla"], a: 2 },
            { q: "¿En qué mes se celebran las Fallas, nuestro paquete estrella?", o: ["Enero", "Julio", "Agosto", "Marzo", "Diciembre"], a: 3 },
            { q: "¿Qué famoso complejo arquitectónico visitamos en el tour cultural?", o: ["Alhambra", "Sagrada Familia", "Ciudad de las Artes y las Ciencias", "Guggenheim", "Mezquita"], a: 2 },
            { q: "¿Cuál es la playa urbana principal de Valencia incluida en el pack verano?", o: ["Barceloneta", "La Malvarrosa", "Baqueira", "Ses Illetes", "Las Canteras"], a: 1 },
    { q: "¿Qué edificio histórico es Patrimonio de la Humanidad y visitamos los martes?", o: ["La Lonja de la Seda", "El Escorial", "Catedral de Burgos", "Alcázar", "Acueducto"], a: 0 },
    { q: "¿Cómo se llama el río cuyo cauce ahora es un gran parque que recorremos en bicicleta?", o: ["Ebro", "Tajo", "Turia", "Guadalquivir", "Duero"], a: 2 },
    { q: "¿Qué bebida típica valenciana degustamos en el centro histórico?", o: ["Sidra", "Horchata", "Sangría", "Rebujito", "Cava"], a: 1 },
    { q: "¿Cuál es el barrio marinero donde terminan nuestras rutas guiadas?", o: ["Triana", "Gracia", "El Cabanyal", "Malasaña", "Albaicín"], a: 2 },
    { q: "¿Cuál es el punto de encuentro para nuestra 'Ruta del Centro Histórico'?", o: ["Plaza Mayor", "Plaza del Sol", "Plaza de la Virgen", "Plaza de España", "Plaza Cataluña"], a: 2 }
];

this.contenedor = document.querySelector("section ");
        this.boton = document.querySelector("button");
        this.mensaje = document.querySelector("main p");
this.generarTablero();
this.configurarEventos();
    }
// Generador DOM
generarTablero() {
   let htmlFormulario = "";
        
        this.questions.forEach((item, index) => {
            htmlFormulario += `<fieldset>`;
            htmlFormulario += `<legend>${index + 1}. ${item.q}</legend>`;
            
            item.o.forEach((option, optIndex) => {
                htmlFormulario += `
                    <label>
                        <input type="radio" name="q${index}" value="${optIndex}">
                        ${option}
                    </label>
                `;
            });
            
            htmlFormulario += `</fieldset>`;
        });

        // Insertamos el HTML de golpe (es mejor práctica de rendimiento)
       this.boton.insertAdjacentHTML('beforebegin', htmlFormulario);
    
    }

    configurarEventos() {
        this.boton.addEventListener("click", () => this.evaluarPuntuacion());
    }
        // Validador y puntuacion
evaluarPuntuacion() {
        let puntuacion = 0;
        let todasRespondidas = true;

        for (let i = 0; i < this.questions.length; i++) {
            const seleccionada = document.querySelector(`input[name="q${i}"]:checked`);
            
            if (!seleccionada) {
                todasRespondidas = false;
                break;
            }
            
            if (parseInt(seleccionada.value, 10) === this.questions[i].a) {
                puntuacion++;
            }
        }

        if (!todasRespondidas) {
            this.mensaje.setAttribute("data-estado", "error");
            this.mensaje.textContent = "Por favor, responde a todas las preguntas antes de finalizar el juego.";
            return;
        }

        this.mensaje.setAttribute("data-estado", "exito");
        this.mensaje.textContent = `¡Has terminado! Tu puntuación es: ${puntuacion} de ${this.questions.length}.`;
    }
    }

// Instanciamos el juego SOLO cuando el HTML haya cargado completamente
if (document.readyState === "loading") {
    document.addEventListener("DOMContentLoaded", () => new JuegoTrivial());
} else {
    new JuegoTrivial();
}