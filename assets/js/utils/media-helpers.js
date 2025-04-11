/**
 * Utilitaires pour générer des balises médias modernes avec fallback
 */

/**
 * Génère une balise <picture> avec WebP et fallback
 * @param {string} src - Chemin de l'image sans extension
 * @param {string} alt - Texte alternatif
 * @param {Object} options - Options supplémentaires
 * @returns {string} - Balise HTML <picture> complète
 */
export function pictureTag(src, alt, options = {}) {
  const {
    className = '',
    width = '',
    height = '',
    loading = 'lazy',
    sizes = '',
    srcsetSizes = [1, 2], // Par défaut 1x et 2x
    originalFormat = 'jpg' // Format d'origine si pas de WebP
  } = options;
  
  // Construire les attributs communs
  const imgAttrs = [
    className ? `class="${className}"` : '',
    width ? `width="${width}"` : '',
    height ? `height="${height}"` : '',
    loading ? `loading="${loading}"` : '',
    sizes ? `sizes="${sizes}"` : '',
    `alt="${alt || ''}"`
  ].filter(Boolean).join(' ');
  
  // Construire le srcset pour WebP
  let webpSrcset = '';
  if (srcsetSizes.length > 0) {
    webpSrcset = srcsetSizes.map(size => 
      `${src}${size > 1 ? '@' + size + 'x' : ''}.webp ${size}x`
    ).join(', ');
  }
  
  // Construire le srcset pour le format original
  let originalSrcset = '';
  if (srcsetSizes.length > 0) {
    originalSrcset = srcsetSizes.map(size => 
      `${src}${size > 1 ? '@' + size + 'x' : ''}.${originalFormat} ${size}x`
    ).join(', ');
  }
  
  return `
<picture>
  <source srcset="${webpSrcset}" type="image/webp">
  <source srcset="${originalSrcset}" type="image/${originalFormat}">
  <img src="${src}.${originalFormat}" ${imgAttrs}>
</picture>`;
}

/**
 * Génère une balise <video> avec WebM et fallback
 * @param {string} src - Chemin de la vidéo sans extension
 * @param {Object} options - Options supplémentaires
 * @returns {string} - Balise HTML <video> complète
 */
export function videoTag(src, options = {}) {
  const {
    className = '',
    width = '',
    height = '',
    autoplay = false,
    controls = true,
    loop = false,
    muted = false,
    poster = '',
    preload = 'metadata',
    playsinline = true,
    fallbackFormat = 'mp4' // Format de fallback si pas de WebM
  } = options;
  
  // Construire les attributs
  const videoAttrs = [
    className ? `class="${className}"` : '',
    width ? `width="${width}"` : '',
    height ? `height="${height}"` : '',
    autoplay ? 'autoplay' : '',
    controls ? 'controls' : '',
    loop ? 'loop' : '',
    muted ? 'muted' : '',
    poster ? `poster="${poster}"` : '',
    `preload="${preload}"`,
    playsinline ? 'playsinline' : ''
  ].filter(Boolean).join(' ');
  
  return `
<video ${videoAttrs}>
  <source src="${src}.webm" type="video/webm">
  <source src="${src}.${fallbackFormat}" type="video/${fallbackFormat}">
  Votre navigateur ne prend pas en charge les vidéos HTML5.
</video>`;
}

/**
 * Remplace toutes les balises img et video dans un conteneur par des versions modernes
 * @param {string} containerId - ID du conteneur HTML
 */
export function upgradeMediaElements(containerId) {
  const container = document.getElementById(containerId);
  if (!container) return;
  
  // Remplacer les images
  container.querySelectorAll('img').forEach(img => {
    const src = img.src.replace(/\.(jpg|jpeg|png|gif|webp)$/, '');
    const alt = img.alt || '';
    const options = {
      className: img.className,
      width: img.width || '',
      height: img.height || '',
      loading: img.loading || 'lazy',
      originalFormat: img.src.split('.').pop()
    };
    
    // Créer l'élément picture
    const tempDiv = document.createElement('div');
    tempDiv.innerHTML = pictureTag(src, alt, options);
    const picture = tempDiv.firstElementChild;
    
    // Remplacer l'image par picture
    img.parentNode.replaceChild(picture, img);
  });
  
  // Remplacer les vidéos
  container.querySelectorAll('video').forEach(video => {
    // Trouver le format actuel
    let currentSrc = '';
    let fallbackFormat = 'mp4';
    
    if (video.querySelector('source')) {
      currentSrc = video.querySelector('source').src.replace(/\.(mp4|webm|mov)$/, '');
      fallbackFormat = video.querySelector('source').src.split('.').pop();
    } else if (video.src) {
      currentSrc = video.src.replace(/\.(mp4|webm|mov)$/, '');
      fallbackFormat = video.src.split('.').pop();
    }
    
    if (!currentSrc) return;
    
    const options = {
      className: video.className,
      width: video.width || '',
      height: video.height || '',
      autoplay: video.autoplay,
      controls: video.controls,
      loop: video.loop,
      muted: video.muted,
      poster: video.poster,
      preload: video.preload || 'metadata',
      playsinline: video.hasAttribute('playsinline'),
      fallbackFormat
    };
    
    // Créer l'élément vidéo moderne
    const tempDiv = document.createElement('div');
    tempDiv.innerHTML = videoTag(currentSrc, options);
    const newVideo = tempDiv.firstElementChild;
    
    // Remplacer la vidéo
    video.parentNode.replaceChild(newVideo, video);
  });
}
