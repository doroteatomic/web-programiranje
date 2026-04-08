const express = require('express');
const path = require('path');
const fs = require('fs');

const app = express();
const PORT = process.env.PORT || 3000;

// EJS postavke
app.set('view engine', 'ejs');
app.set('views', path.join(__dirname, 'views'));

// Statičke datoteke iz public mape
app.use(express.static(path.join(__dirname, 'public')));

// Početna stranica
app.get('/', (req, res) => {
    res.sendFile(path.join(__dirname, 'public', 'index.html'));
});

// Galerija slika
app.get('/slike', (req, res) => {
    const folderPath = path.join(__dirname, 'public', 'images');

    try {
        const files = fs.readdirSync(folderPath);

        const images = files
            .filter(file =>
                file.endsWith('.jpg') ||
                file.endsWith('.jpeg') ||
                file.endsWith('.png') ||
                file.endsWith('.svg') ||
                file.endsWith('.webp')
            )
            .map((file, index) => ({
                url: `/images/${file}`,
                id: `slika${index + 1}`,
                title: `Slika ${index + 1}`
            }));

        res.render('slike', { images });
    } catch (error) {
        console.error('Greška pri učitavanju slika:', error);
        res.status(500).send('Greška pri učitavanju galerije.');
    }
});

// Pokretanje servera
app.listen(PORT, () => {
    console.log(`Server radi na portu ${PORT}`);
});
