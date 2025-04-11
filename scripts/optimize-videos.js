const fs = require('fs');
const path = require('path');
const { execSync } = require('child_process');
const ffmpegPath = require('ffmpeg-static');

// Configuration
const sourceDir = path.resolve(__dirname, '../assets/videos');
const publicDir = path.resolve(__dirname, '../public/videos');

// Ensure public directory exists
if (!fs.existsSync(publicDir)) {
  fs.mkdirSync(publicDir, { recursive: true });
}

// Get all video files
function getVideoFiles(dir, fileList = []) {
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
      
      getVideoFiles(filePath, fileList);
    } else if (/\.(mp4|webm|mov|avi)$/i.test(file)) {
      const relativePath = path.relative(sourceDir, dir);
      const fileName = file;
      const fileNameWithoutExt = path.basename(file, path.extname(file));
      
      fileList.push({
        input: filePath,
        // Public directory outputs
        publicMp4: path.join(publicDir, relativePath, fileName),
        publicWebm: path.join(publicDir, relativePath, `${fileNameWithoutExt}.webm`)
      });
    }
  });
  
  return fileList;
}

// Optimize videos and convert to WebM
function optimizeVideos() {
  const videos = getVideoFiles(sourceDir);
  
  if (videos.length === 0) {
    console.log('No videos found to optimize.');
    return;
  }
  
  console.log(`Found ${videos.length} videos to optimize and convert to WebM.`);
  
  videos.forEach(video => {
    try {
      const relativePath = path.relative(sourceDir, video.input);
      console.log(`Processing: ${relativePath}`);
      
      // Create public directory if it doesn't exist
      const publicOutputDir = path.dirname(video.publicWebm);
      if (!fs.existsSync(publicOutputDir)) {
        fs.mkdirSync(publicOutputDir, { recursive: true });
      }
      
      // 1. Copy/Optimize MP4 to public directory
      const needsMp4 = !fs.existsSync(video.publicMp4) || 
        fs.statSync(video.input).mtimeMs > fs.statSync(video.publicMp4).mtimeMs;
      
      if (needsMp4) {
        console.log(`  Optimizing MP4 directly to public directory...`);
        
        // Different settings based on file extension
        const isMP4 = video.input.endsWith('.mp4');
        
        if (isMP4) {
          const optimizeCommand = `"${ffmpegPath}" -i "${video.input}" -c:v libx264 -crf 23 -preset medium -c:a aac -b:a 128k "${video.publicMp4}"`;
          execSync(optimizeCommand, { stdio: 'inherit' });
        } else {
          // If not MP4, convert to MP4
          const convertCommand = `"${ffmpegPath}" -i "${video.input}" -c:v libx264 -crf 23 -preset medium -c:a aac -b:a 128k "${video.publicMp4}"`;
          execSync(convertCommand, { stdio: 'inherit' });
        }
        
        console.log('    Done optimizing MP4!');
      } else {
        console.log('  MP4 already in public directory, skipping...');
      }
      
      // 2. Convert to WebM directly to public directory
      const needsWebM = !fs.existsSync(video.publicWebm) || 
        fs.statSync(video.input).mtimeMs > fs.statSync(video.publicWebm).mtimeMs;
      
      if (needsWebM) {
        console.log(`  Converting to WebM directly to public directory...`);
        
        // Use high-quality WebM settings with VP9 codec
        const webmCommand = `"${ffmpegPath}" -i "${video.input}" -c:v libvpx-vp9 -crf 30 -b:v 0 -deadline good -cpu-used 2 -c:a libopus -b:a 96k "${video.publicWebm}"`;
        
        execSync(webmCommand, { stdio: 'inherit' });
        console.log('    Done converting to WebM!');
      } else {
        console.log('  WebM already in public directory, skipping...');
      }
      
    } catch (error) {
      console.error(`Error processing ${video.input}:`, error.message);
    }
  });
  
  console.log('Video optimization and WebM conversion complete!');
}

// Run optimization
optimizeVideos();
