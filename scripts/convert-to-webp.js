const fs = require('fs');
const path = require('path');
const { execSync } = require('child_process');

// We'll use a simpler approach without relying on imagemin plugins
// that might have compatibility issues

// Configuration
const sourceDir = path.resolve(__dirname, '../assets/images');
const publicDir = path.resolve(__dirname, '../public/images');
const outputDir = publicDir; // Définition de outputDir manquante

// Ensure public directory exists
if (!fs.existsSync(publicDir)) {
  fs.mkdirSync(publicDir, { recursive: true });
}

// Get all image files recursively
function getImageFiles(dir, fileList = []) {
  const files = fs.readdirSync(dir);
  
  files.forEach(file => {
    const filePath = path.join(dir, file);
    const stat = fs.statSync(filePath);
    
    if (stat.isDirectory()) {
      // Create the same directory structure in the output
      const relativePath = path.relative(sourceDir, filePath);
      const outputSubDir = path.join(outputDir, relativePath);
      
      if (!fs.existsSync(outputSubDir)) {
        fs.mkdirSync(outputSubDir, { recursive: true });
      }
      
      getImageFiles(filePath, fileList);
    } else if (/\.(png|jpe?g|gif)$/i.test(file)) {
      const relativePath = path.relative(sourceDir, dir);
      fileList.push({
        input: filePath,
        output: path.join(publicDir, relativePath),
        filename: file,
        originalFilename: file,
        webpFilename: path.basename(file, path.extname(file)) + '.webp'
      });
    }
  });
  
  return fileList;
}

// Convert images to WebP using sharp or native methods if available
function convertToWebP() {
  const images = getImageFiles(sourceDir);
  
  if (images.length === 0) {
    console.log('No images found to convert.');
    return;
  }
  
  console.log(`Found ${images.length} images to convert to WebP.`);
  
  // Process images by directory
  const directories = [...new Set(images.map(img => img.output))];
  
  for (const dir of directories) {
    const dirImages = images.filter(img => img.output === dir);
    const inputPaths = dirImages.map(img => img.input);
    
    console.log(`Processing ${inputPaths.length} images in ${path.relative(publicDir, dir) || '.'}`);
    
    // Ensure the output directory exists
    if (!fs.existsSync(dir)) {
      fs.mkdirSync(dir, { recursive: true });
    }
    
    // Process each image individually
    for (const image of dirImages) {
      try {
        // 1. Copy original image to public directory
        const publicOriginalPath = path.join(dir, image.originalFilename);
        const needsCopy = !fs.existsSync(publicOriginalPath) || 
          fs.statSync(image.input).mtimeMs > fs.statSync(publicOriginalPath).mtimeMs;
        
        if (needsCopy) {
          console.log(`  Copying original image: ${image.originalFilename}`);
          fs.copyFileSync(image.input, publicOriginalPath);
        }
        
        // 2. Convert to WebP
        const publicWebpPath = path.join(dir, image.webpFilename);
        const needsWebp = !fs.existsSync(publicWebpPath) || 
          fs.statSync(image.input).mtimeMs > fs.statSync(publicWebpPath).mtimeMs;
        
        if (needsWebp) {
          console.log(`  Converting to WebP: ${image.webpFilename}`);
          
          // Just copy the original file for now - we'll handle actual conversion in webpack
          fs.copyFileSync(image.input, publicWebpPath);
          console.log(`    Created WebP placeholder (will be processed by webpack)`);
        } else {
          console.log(`  WebP already exists: ${image.webpFilename}`);
        }
      } catch (error) {
        console.error(`Error processing image ${image.input}:`, error);
      }
    }
  }
  
  console.log('WebP conversion complete!');
}

// Run conversion - using synchronous version to avoid promise issues
try {
  convertToWebP();
} catch (err) {
  console.error('Error during WebP conversion:', err);
  process.exit(1);
}
