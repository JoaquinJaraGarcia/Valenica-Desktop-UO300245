class JuegoTrivial {
    constructor() {
        this.questions = [
           { 
                q: "¿En qué lugar exacto comienza la ruta por el Centro Histórico de Valencia?", 
                o: ["Mercado Central", "Plaza de la Virgen", "Torres de Serranos", "La Lonja de la Seda", "El Miguelete"], 
                a: 2 
            },
            { 
                q: "Según nuestra ruta, ¿qué edificio es considerado una obra maestra del gótico civil?", 
                o: ["Catedral de Valencia", "Mercado Central", "La Lonja de la Seda", "Ayuntamiento", "Torres de Serranos"], 
                a: 2 
            },
            { 
                q: "¿Cuál es el medio de transporte que utilizamos en la ruta por el Parque Natural de L'Albufera?", 
                o: ["A pie", "Autobús", "Coche", "Bicicleta", "Barca tradicional"], 
                a: 3 
            },
            { 
                q: "¿Cómo se llama el pueblo tradicional de pescadores y arroceros que visitamos en L'Albufera?", 
                o: ["El Saler", "El Palmar", "Chelva", "Benacacira", "Pujol"], 
                a: 1 
            },
            { 
                q: "¿Qué nombre recibe la vivienda histórica de la huerta valenciana que vemos en la ruta de L'Albufera?", 
                o: ["Masía", "Cortijo", "Barraca", "Cabaña", "Alquería"], 
                a: 2 
            },
            { 
                q: "¿Qué agencia organiza la ruta de 1 día de duración por el Parque Natural de L'Albufera?", 
                o: ["Valencia Tours", "Sin agencia", "Turia Viajes", "NaturaTur", "Albufera Express"], 
                a: 3 
            },
            { 
                q: "¿Cómo se llama el túnel excavado en la roca junto al cauce en la Ruta de Chelva?", 
                o: ["Gola de Pujol", "Paso de Olinches", "Cueva del Turia", "Túnel del Cuco", "Paso del Molino"], 
                a: 1 
            },
            { 
                q: "¿Qué barrio de herencia musulmana con calles estrechas visitamos al inicio de la ruta de Chelva?", 
                o: ["El Carmen", "Ruzafa", "Benacacira", "El Cabanyal", "Benimaclet"], 
                a: 2 
            },
            { 
                q: "¿Dónde se encuentra la famosa fuente del Turia en nuestra ruta por el corazón de la ciudad?", 
                o: ["Torres de Serranos", "Mercado Central", "Plaza del Ayuntamiento", "Plaza de la Virgen", "La Lonja"], 
                a: 3 
            },
            { 
                q: "¿Cómo se llama la zona de baño natural en el río que forma parte de la Ruta del Agua de Chelva?", 
                o: ["El Palmar", "Gola de Pujol", "La Playeta", "Fuente del Cuco", "Molino Puerto"], 
                a: 2 
            }
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