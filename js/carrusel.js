class Carrusel {
    constructor() {
        this.actual = 0;
        this.fotografias = [
            { url: 'multimedia/MapaProvincia.jpg', titulo: 'Mapa de la provincia', autor: 'Diputación' },
            { url: 'multimedia/recurso1.jpg', titulo: 'Ciudad de las artes y ciencias', autor: 'Autor 1' },
            { url: 'multimedia/recurso2.jpg', titulo: 'Castillo de Xativa', autor: 'Autor 2' },
            { url: 'multimedia/recurso3.jpg', titulo: 'Parque natural de la Albufera', autor: 'Autor 3' },
            { url: 'multimedia/recurso4.jpg', titulo: 'Teatro romano de Sagunto', autor: 'Autor 4' }
        ];
        this.intervalo = null;
    }

    mostrarFotografias(contenedor, tiempo = 3000) {
        const $contenedor = $(contenedor);
        if ($contenedor.length === 0) return;

        this.cambiarFotografia();
        this.intervalo = setInterval(this.cambiarFotografia.bind(this), tiempo);
    }

    cambiarFotografia() {
        const foto = this.fotografias[this.actual];

        const $img = $('<img>')
            .attr('src', foto.url)
            .attr('alt', foto.titulo)
            .addClass('img-carrusel');
        let $contenedor = $('h2:nth-of-type(1)').next('figure');
        if ($contenedor.length === 0) {
            $contenedor = $('<figure>');
            $('h2:nth-of-type(1)').after($contenedor);
        }
        $contenedor.empty().append($img);
        this.actual = (this.actual + 1) % this.fotografias.length;
    }
}



