const thumbnails = document.querySelectorAll('.thumbnail');
const mainImage = document.getElementById('current-image');
let currentIndex = 0;


console.log('Hello from card.js');

thumbnails.forEach((thumb, index) => {
  thumb.addEventListener('click', () => {
    document.querySelector('.thumbnail.active').classList.remove('active');
    thumb.classList.add('active');
    mainImage.src = thumb.src;
    currentIndex = index;
  });
});

document.querySelector('.next-btn').addEventListener('click', () => {
  currentIndex = (currentIndex + 1) % thumbnails.length;
  thumbnails[currentIndex].click();
});

document.querySelector('.prev-btn').addEventListener('click', () => {
  currentIndex = (currentIndex - 1 + thumbnails.length) % thumbnails.length;
  thumbnails[currentIndex].click();
});
