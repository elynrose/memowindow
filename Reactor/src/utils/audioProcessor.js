// Audio processing utilities for waveform generation
export class AudioProcessor {
  // Compute peaks from audio buffer (ported from original app)
  static computePeaksFromBuffer(buf, width = 2000) {
    const ch = buf.getChannelData(0);
    const hop = Math.max(1, Math.floor(buf.length / width));
    const min = new Float32Array(width);
    const max = new Float32Array(width);
    
    for (let x = 0; x < width; x++) {
      const s = x * hop;
      const e = Math.min((x + 1) * hop, buf.length);
      let mi = 1.0;
      let ma = -1.0;
      
      for (let i = s; i < e; i++) { 
        const v = ch[i]; 
        if (v < mi) mi = v; 
        if (v > ma) ma = v; 
      }
      min[x] = mi; 
      max[x] = ma;
    }
    return { min, max };
  }

  // Process audio file for preview
  static async processFileForPreview(file) {
    try {
      const arrayBuffer = await file.arrayBuffer();
      const audioContext = new (window.AudioContext || window.webkitAudioContext)();
      const audioBuffer = await audioContext.decodeAudioData(arrayBuffer);
      
      // Compute peaks for preview
      const peaks = this.computePeaksFromBuffer(audioBuffer);
      return { success: true, peaks, audioBuffer };
    } catch (error) {
      console.error('Error processing file for preview:', error);
      return { success: false, error: error.message };
    }
  }

  // Create waveform from audio (high resolution for print quality)
  static async createWaveformFromAudio(audioFile, title, qrCodeUrl = null) {
    try {
      const arrayBuffer = await audioFile.arrayBuffer();
      const audioContext = new (window.AudioContext || window.webkitAudioContext)();
      const audioBuffer = await audioContext.decodeAudioData(arrayBuffer);
      
      // Create high-resolution canvas for print quality
      const W = 3600;
      const H = 2400;
      const canvas = document.createElement('canvas');
      canvas.width = W;
      canvas.height = H;
      const ctx = canvas.getContext('2d');
      
      // Background
      ctx.fillStyle = '#ffffff';
      ctx.fillRect(0, 0, W, H);
      
      // Compute peaks
      const peaks = this.computePeaksFromBuffer(audioBuffer);
      
      // Calculate layout
      const titleHeight = Math.floor(H * 0.13); // 13% for title
      const qrSize = Math.floor(H * 0.2); // 20% for QR code
      const padding = Math.floor(W * 0.02); // 2% padding
      const waveformWidth = W * 0.7; // 70% of canvas width
      const waveformArea = {
        x: (W - waveformWidth) / 2, // Center the waveform
        y: titleHeight + padding,
        width: waveformWidth,
        height: H - titleHeight - qrSize - (padding * 3)
      };
      
      // Draw title
      ctx.fillStyle = '#0b0d12';
      const fontSize = Math.floor(W * 0.024); // Responsive font size
      ctx.font = `bold ${fontSize}px system-ui, -apple-system, sans-serif`;
      ctx.textAlign = 'center';
      ctx.textBaseline = 'middle';
      
      // Word wrap title
      const maxTitleWidth = W - (padding * 2);
      const words = title.split(' ');
      let line = '';
      let lines = [];
      
      for (let n = 0; n < words.length; n++) {
        const testLine = line + words[n] + ' ';
        const metrics = ctx.measureText(testLine);
        const testWidth = metrics.width;
        
        if (testWidth > maxTitleWidth && n > 0) {
          lines.push(line);
          line = words[n] + ' ';
        } else {
          line = testLine;
        }
      }
      lines.push(line);
      
      // Draw title lines
      const lineHeight = fontSize * 1.2;
      const titleStartY = titleHeight / 2 - ((lines.length - 1) * lineHeight / 2);
      lines.forEach((line, index) => {
        ctx.fillText(line.trim(), W / 2, titleStartY + (index * lineHeight));
      });
      
      // Draw waveform (using bars like original)
      ctx.fillStyle = '#000';
      const waveformPad = waveformArea.height * 0.1;
      const yMap = (v) => { 
        return waveformArea.y + waveformArea.height - waveformPad - (v + 1) / 2 * (waveformArea.height - 2 * waveformPad); 
      };
      
      for (let x = 0; x < waveformArea.width; x++) {
        const i = Math.floor(x * peaks.min.length / waveformArea.width);
        const y1 = yMap(peaks.max[i]);
        const y2 = yMap(peaks.min[i]);
        const lineWidth = Math.max(1, Math.floor(waveformArea.width / 800)); // Responsive line width
        ctx.fillRect(waveformArea.x + x, y1, lineWidth, Math.max(1, y2 - y1));
      }
      
      // Draw QR code if provided
      if (qrCodeUrl) {
        try {
          await this.drawQRCode(ctx, padding, H - qrSize - padding, qrSize, qrCodeUrl);
        } catch (error) {
          console.error('Failed to draw QR code:', error);
          // Draw placeholder if QR fails
          this.drawQRPlaceholder(ctx, padding, H - qrSize - padding, qrSize);
        }
      } else {
        this.drawQRPlaceholder(ctx, padding, H - qrSize - padding, qrSize);
      }
      
      return new Promise(resolve => canvas.toBlob(resolve, 'image/png'));
    } catch (error) {
      console.error('Error creating waveform:', error);
      throw error;
    }
  }

  // Draw QR code
  static async drawQRCode(ctx, x, y, size, qrUrl) {
    return new Promise((resolve, reject) => {
      const img = new Image();
      img.crossOrigin = 'anonymous';
      img.onload = () => {
        ctx.drawImage(img, x, y, size, size);
        resolve();
      };
      img.onerror = reject;
      img.src = qrUrl;
    });
  }

  // Draw QR placeholder
  static drawQRPlaceholder(ctx, x, y, size) {
    ctx.fillStyle = '#f3f4f6';
    ctx.fillRect(x, y, size, size);
    ctx.strokeStyle = '#e5e7eb';
    ctx.lineWidth = 3;
    ctx.strokeRect(x, y, size, size);
    
    ctx.fillStyle = '#6b7280';
    const fontSize = Math.floor(size * 0.08);
    ctx.font = `${fontSize}px system-ui, -apple-system, sans-serif`;
    ctx.textAlign = 'center';
    ctx.textBaseline = 'middle';
    ctx.fillText('QR Code', x + size/2, y + size/2);
  }

  // Draw preview (lower resolution for UI)
  static drawPreview(canvas, peaks, title) {
    if (!canvas || !peaks) return;
    
    const ctx = canvas.getContext('2d');
    const W = canvas.width;
    const H = canvas.height;
    
    // Clear canvas
    ctx.fillStyle = '#ffffff';
    ctx.fillRect(0, 0, W, H);
    
    // Calculate layout areas
    const titleHeight = 80;
    const qrSize = 120;
    const padding = 40;
    const waveformWidth = W * 0.7; // 70% of canvas width
    const waveformArea = {
      x: (W - waveformWidth) / 2, // Center the waveform
      y: titleHeight + padding,
      width: waveformWidth,
      height: H - titleHeight - qrSize - (padding * 3)
    };
    
    // Draw title at top center
    ctx.fillStyle = '#0b0d12';
    ctx.font = 'bold 32px system-ui, -apple-system, sans-serif';
    ctx.textAlign = 'center';
    ctx.textBaseline = 'middle';
    
    // Word wrap title if too long
    const maxTitleWidth = W - (padding * 2);
    const words = title.split(' ');
    let line = '';
    let lines = [];
    
    for (let n = 0; n < words.length; n++) {
      const testLine = line + words[n] + ' ';
      const metrics = ctx.measureText(testLine);
      const testWidth = metrics.width;
      
      if (testWidth > maxTitleWidth && n > 0) {
        lines.push(line);
        line = words[n] + ' ';
      } else {
        line = testLine;
      }
    }
    lines.push(line);
    
    // Draw title lines
    const lineHeight = 40;
    const titleStartY = titleHeight / 2 - ((lines.length - 1) * lineHeight / 2);
    lines.forEach((line, index) => {
      ctx.fillText(line.trim(), W / 2, titleStartY + (index * lineHeight));
    });
    
    // Draw waveform in center area (using bars like original)
    ctx.fillStyle = '#000';
    const waveformPad = waveformArea.height * 0.1;
    const yMap = (v) => { 
      return waveformArea.y + waveformArea.height - waveformPad - (v + 1) / 2 * (waveformArea.height - 2 * waveformPad); 
    };
    
    for (let x = 0; x < waveformArea.width; x++) {
      const i = Math.floor(x * peaks.min.length / waveformArea.width);
      const y1 = yMap(peaks.max[i]);
      const y2 = yMap(peaks.min[i]);
      const lineWidth = Math.max(1, Math.floor(waveformArea.width / 800)); // Responsive line width
      ctx.fillRect(waveformArea.x + x, y1, lineWidth, Math.max(1, y2 - y1));
    }
    
    // Add QR code placeholder
    this.drawQRPlaceholder(ctx, padding, H - qrSize - padding, qrSize);
  }
}