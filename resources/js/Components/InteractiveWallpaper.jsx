import { useEffect, useRef } from 'react';

export default function InteractiveWallpaper() {
    const canvasRef = useRef(null);

    useEffect(() => {
        const canvas = canvasRef.current;
        if (!canvas) return;

        const ctx = canvas.getContext('2d');
        if (!ctx) return;

        let animationFrameId;
        let width = (canvas.width = window.innerWidth);
        let height = (canvas.height = window.innerHeight);

        // State variables
        const particles = [];
        const tempParticles = [];
        const shockwaves = [];
        const mouse = { x: null, y: null, active: false, isDown: false };

        // Configuration
        const MAX_PARTICLES = Math.min(80, Math.floor((width * height) / 15000));
        const CONNECTION_DIST = 110;
        const MOUSE_CONNECTION_DIST = 180;
        const MAGNETIC_DIST = 200;
        const PULL_FORCE = 0.08;

        // Colors
        const THEME_COLORS = [
            'rgba(6, 182, 212, 1)',   // Cyan
            'rgba(168, 85, 247, 1)',  // Purple/Violet
            'rgba(59, 130, 246, 1)',  // Blue
        ];

        // Background glows tracking
        const glows = [
            { x: width * 0.3, y: height * 0.3, vx: 0.5, vy: 0.3, radius: Math.min(width, height) * 0.4, color: 'rgba(6, 182, 212, 0.08)' },
            { x: width * 0.7, y: height * 0.6, vx: -0.4, vy: 0.4, radius: Math.min(width, height) * 0.45, color: 'rgba(168, 85, 247, 0.06)' },
            { x: width * 0.5, y: height * 0.8, vx: 0.3, vy: -0.5, radius: Math.min(width, height) * 0.35, color: 'rgba(59, 130, 246, 0.06)' }
        ];

        // Particle Class definition helper
        class Particle {
            constructor(x, y, isTemp = false, vx, vy) {
                this.x = x || Math.random() * width;
                this.y = y || Math.random() * height;
                this.vx = vx !== undefined ? vx : (Math.random() - 0.5) * 0.8;
                this.vy = vy !== undefined ? vy : (Math.random() - 0.5) * 0.8;
                this.baseRadius = isTemp ? Math.random() * 2 + 1 : Math.random() * 2 + 1.5;
                this.radius = this.baseRadius;
                this.color = THEME_COLORS[Math.floor(Math.random() * THEME_COLORS.length)];
                this.alpha = isTemp ? 1.0 : Math.random() * 0.5 + 0.3;
                this.isTemp = isTemp;
                this.life = isTemp ? Math.random() * 60 + 40 : null; // frames to live
                this.maxLife = this.life;
            }

            update() {
                // Move
                this.x += this.vx;
                this.y += this.vy;

                // Frictions/Damping
                this.vx *= 0.99;
                this.vy *= 0.99;

                // Restoring base speed for permanent particles
                if (!this.isTemp) {
                    const speed = Math.sqrt(this.vx * this.vx + this.vy * this.vy);
                    if (speed < 0.2) {
                        this.vx += (Math.random() - 0.5) * 0.1;
                        this.vy += (Math.random() - 0.5) * 0.1;
                    }
                }

                // Bounce off boundaries
                if (!this.isTemp) {
                    if (this.x < 0 || this.x > width) this.vx *= -1;
                    if (this.y < 0 || this.y > height) this.vy *= -1;
                    // Clamp inside
                    this.x = Math.max(0, Math.min(width, this.x));
                    this.y = Math.max(0, Math.min(height, this.y));
                }

                // React to mouse magnetism
                if (mouse.active && mouse.x !== null && mouse.y !== null) {
                    const dx = mouse.x - this.x;
                    const dy = mouse.y - this.y;
                    const dist = Math.sqrt(dx * dx + dy * dy);

                    if (dist < MAGNETIC_DIST) {
                        // Magnetic pull towards cursor
                        const force = (1 - dist / MAGNETIC_DIST) * PULL_FORCE;
                        this.vx += (dx / dist) * force;
                        this.vy += (dy / dist) * force;
                        
                        // Grow slightly if close to cursor (tactile feedback)
                        this.radius = this.baseRadius * (1 + (1 - dist / MAGNETIC_DIST) * 1.2);
                    } else {
                        // Slowly ease back to base radius
                        this.radius += (this.baseRadius - this.radius) * 0.1;
                    }
                } else {
                    this.radius += (this.baseRadius - this.radius) * 0.1;
                }

                // Life cycle for temporary particles
                if (this.isTemp && this.life !== null) {
                    this.life--;
                    this.alpha = Math.max(0, this.life / this.maxLife);
                }
            }

            draw() {
                ctx.beginPath();
                ctx.arc(this.x, this.y, this.radius, 0, Math.PI * 2);
                
                // Set fill style with opacity
                const rgba = this.color.replace('1)', `${this.alpha})`);
                ctx.fillStyle = rgba;
                
                // Add glowing styling only if close to cursor
                if (this.radius > this.baseRadius * 1.5) {
                    ctx.shadowBlur = 8;
                    ctx.shadowColor = this.color;
                } else {
                    ctx.shadowBlur = 0;
                }
                
                ctx.fill();
                ctx.shadowBlur = 0; // reset
            }
        }

        // Initialize permanent particles
        for (let i = 0; i < MAX_PARTICLES; i++) {
            particles.push(new Particle());
        }

        // Event listeners
        const handleMouseMove = (e) => {
            mouse.x = e.clientX;
            mouse.y = e.clientY;
            mouse.active = true;

            // Fidget feature: spawn temporary particles on drag/mouse-down movement
            if (mouse.isDown) {
                for (let i = 0; i < 2; i++) {
                    const angle = Math.random() * Math.PI * 2;
                    const speed = Math.random() * 2 + 1;
                    tempParticles.push(new Particle(
                        mouse.x,
                        mouse.y,
                        true,
                        Math.cos(angle) * speed,
                        Math.sin(angle) * speed
                    ));
                }
            }
        };

        const handleMouseLeave = () => {
            mouse.active = false;
            mouse.x = null;
            mouse.y = null;
            mouse.isDown = false;
        };

        const handleMouseDown = (e) => {
            mouse.isDown = true;
            
            // Trigger shockwave
            shockwaves.push({
                x: e.clientX,
                y: e.clientY,
                radius: 0,
                maxRadius: 280,
                speed: 8,
                force: 12
            });

            // Burst of new particles on click
            const burstCount = 12;
            for (let i = 0; i < burstCount; i++) {
                const angle = (i / burstCount) * Math.PI * 2 + Math.random() * 0.5;
                const speed = Math.random() * 3 + 2;
                tempParticles.push(new Particle(
                    e.clientX,
                    e.clientY,
                    true,
                    Math.cos(angle) * speed,
                    Math.sin(angle) * speed
                ));
            }
        };

        const handleMouseUp = () => {
            mouse.isDown = false;
        };

        const handleResize = () => {
            width = canvas.width = window.innerWidth;
            height = canvas.height = window.innerHeight;
            
            // Adjust particle limit and re-initialize glows
            const newLimit = Math.min(80, Math.floor((width * height) / 15000));
            if (particles.length < newLimit) {
                while (particles.length < newLimit) {
                    particles.push(new Particle());
                }
            } else if (particles.length > newLimit) {
                particles.splice(newLimit);
            }

            glows[0].radius = Math.min(width, height) * 0.4;
            glows[1].radius = Math.min(width, height) * 0.45;
            glows[2].radius = Math.min(width, height) * 0.35;
        };

        // Touch event handling for mobile devices
        const handleTouchMove = (e) => {
            if (e.touches.length > 0) {
                mouse.x = e.touches[0].clientX;
                mouse.y = e.touches[0].clientY;
                mouse.active = true;

                if (mouse.isDown) {
                    tempParticles.push(new Particle(
                        mouse.x,
                        mouse.y,
                        true,
                        (Math.random() - 0.5) * 3,
                        (Math.random() - 0.5) * 3
                    ));
                }
            }
        };

        const handleTouchStart = (e) => {
            mouse.isDown = true;
            if (e.touches.length > 0) {
                const clientX = e.touches[0].clientX;
                const clientY = e.touches[0].clientY;
                mouse.x = clientX;
                mouse.y = clientY;
                mouse.active = true;

                shockwaves.push({
                    x: clientX,
                    y: clientY,
                    radius: 0,
                    maxRadius: 240,
                    speed: 7,
                    force: 10
                });

                const burstCount = 8;
                for (let i = 0; i < burstCount; i++) {
                    const angle = (i / burstCount) * Math.PI * 2;
                    const speed = Math.random() * 3 + 1.5;
                    tempParticles.push(new Particle(
                        clientX,
                        clientY,
                        true,
                        Math.cos(angle) * speed,
                        Math.sin(angle) * speed
                    ));
                }
            }
        };

        window.addEventListener('mousemove', handleMouseMove);
        window.addEventListener('mouseleave', handleMouseLeave);
        window.addEventListener('mousedown', handleMouseDown);
        window.addEventListener('mouseup', handleMouseUp);
        window.addEventListener('resize', handleResize);
        
        window.addEventListener('touchmove', handleTouchMove, { passive: true });
        window.addEventListener('touchstart', handleTouchStart, { passive: true });
        window.addEventListener('touchend', handleMouseUp);

        // Main Animation loop
        const animate = () => {
            // 1. Draw Deep Cyber Background
            ctx.fillStyle = '#060814';
            ctx.fillRect(0, 0, width, height);

            // 2. Animate and Draw background drifting glowing halos
            glows.forEach((glow) => {
                glow.x += glow.vx;
                glow.y += glow.vy;

                // Bounce glows off walls
                if (glow.x - glow.radius < 0 || glow.x + glow.radius > width) glow.vx *= -1;
                if (glow.y - glow.radius < 0 || glow.y + glow.radius > height) glow.vy *= -1;

                const grad = ctx.createRadialGradient(glow.x, glow.y, 0, glow.x, glow.y, glow.radius);
                grad.addColorStop(0, glow.color);
                grad.addColorStop(1, 'rgba(6, 8, 20, 0)');
                ctx.fillStyle = grad;
                ctx.beginPath();
                ctx.arc(glow.x, glow.y, glow.radius, 0, Math.PI * 2);
                ctx.fill();
            });

            // 3. Update & Draw Shockwaves
            for (let s = shockwaves.length - 1; s >= 0; s--) {
                const wave = shockwaves[s];
                wave.radius += wave.speed;

                // Draw shockwave boundary faintly
                ctx.beginPath();
                ctx.arc(wave.x, wave.y, wave.radius, 0, Math.PI * 2);
                const waveAlpha = Math.max(0, 1 - wave.radius / wave.maxRadius);
                ctx.strokeStyle = `rgba(6, 182, 212, ${waveAlpha * 0.25})`;
                ctx.lineWidth = 3;
                ctx.stroke();

                // Apply shockwave physics to all permanent and temp particles
                const allParticles = [...particles, ...tempParticles];
                allParticles.forEach((p) => {
                    const dx = p.x - wave.x;
                    const dy = p.y - wave.y;
                    const dist = Math.sqrt(dx * dx + dy * dy);

                    // If particle is near wavefront
                    if (Math.abs(dist - wave.radius) < 25 && dist > 10) {
                        const push = (1 - wave.radius / wave.maxRadius) * wave.force;
                        p.vx += (dx / dist) * push * 0.8;
                        p.vy += (dy / dist) * push * 0.8;
                    }
                });

                if (wave.radius >= wave.maxRadius) {
                    shockwaves.splice(s, 1);
                }
            }

            // 4. Update and filter temporary particles
            for (let i = tempParticles.length - 1; i >= 0; i--) {
                const p = tempParticles[i];
                p.update();
                if (p.life <= 0) {
                    tempParticles.splice(i, 1);
                } else {
                    p.draw();
                }
            }

            // 5. Update and Draw permanent particles
            particles.forEach((p) => {
                p.update();
                p.draw();
            });

            // 6. Draw connection lines (Plexus)
            // Draw connections between permanent particles
            for (let i = 0; i < particles.length; i++) {
                const pi = particles[i];
                for (let j = i + 1; j < particles.length; j++) {
                    const pj = particles[j];
                    const dx = pi.x - pj.x;
                    const dy = pi.y - pj.y;
                    const dist = Math.sqrt(dx * dx + dy * dy);

                    if (dist < CONNECTION_DIST) {
                        const alpha = (1 - dist / CONNECTION_DIST) * 0.16;
                        ctx.beginPath();
                        ctx.moveTo(pi.x, pi.y);
                        ctx.lineTo(pj.x, pj.y);
                        
                        // Select border gradient style
                        ctx.strokeStyle = `rgba(168, 85, 247, ${alpha})`; // Purple connections
                        ctx.lineWidth = 0.8;
                        ctx.stroke();
                    }
                }

                // Connect to mouse
                if (mouse.active && mouse.x !== null && mouse.y !== null) {
                    const dx = pi.x - mouse.x;
                    const dy = pi.y - mouse.y;
                    const dist = Math.sqrt(dx * dx + dy * dy);

                    if (dist < MOUSE_CONNECTION_DIST) {
                        const alpha = (1 - dist / MOUSE_CONNECTION_DIST) * 0.35;
                        ctx.beginPath();
                        ctx.moveTo(pi.x, pi.y);
                        ctx.lineTo(mouse.x, mouse.y);
                        ctx.strokeStyle = `rgba(6, 182, 212, ${alpha})`; // Bright cyan to cursor
                        ctx.lineWidth = 1.0;
                        ctx.stroke();
                    }
                }
            }

            // Draw connections for temporary particles (sparks link to cursor if close)
            tempParticles.forEach((tp) => {
                if (mouse.active && mouse.x !== null && mouse.y !== null) {
                    const dx = tp.x - mouse.x;
                    const dy = tp.y - mouse.y;
                    const dist = Math.sqrt(dx * dx + dy * dy);
                    if (dist < 80) {
                        const alpha = (1 - dist / 80) * tp.alpha * 0.25;
                        ctx.beginPath();
                        ctx.moveTo(tp.x, tp.y);
                        ctx.lineTo(mouse.x, mouse.y);
                        ctx.strokeStyle = `rgba(59, 130, 246, ${alpha})`;
                        ctx.lineWidth = 0.6;
                        ctx.stroke();
                    }
                }
            });

            // Draw dynamic cursor hub in canvas
            if (mouse.active && mouse.x !== null && mouse.y !== null) {
                // Interactive core pulsing glow
                const time = Date.now() * 0.005;
                const glowRadius = 4 + Math.sin(time) * 1.5;
                
                ctx.beginPath();
                ctx.arc(mouse.x, mouse.y, glowRadius, 0, Math.PI * 2);
                ctx.fillStyle = 'rgba(6, 182, 212, 0.8)';
                ctx.shadowBlur = 15;
                ctx.shadowColor = 'rgb(6, 182, 212)';
                ctx.fill();
                ctx.shadowBlur = 0; // reset

                // Outer faint radar circle
                ctx.beginPath();
                ctx.arc(mouse.x, mouse.y, glowRadius * 3, 0, Math.PI * 2);
                ctx.strokeStyle = 'rgba(6, 182, 212, 0.2)';
                ctx.lineWidth = 1;
                ctx.stroke();
            }

            animationFrameId = requestAnimationFrame(animate);
        };

        animate();

        // Cleanup
        return () => {
            cancelAnimationFrame(animationFrameId);
            window.removeEventListener('mousemove', handleMouseMove);
            window.removeEventListener('mouseleave', handleMouseLeave);
            window.removeEventListener('mousedown', handleMouseDown);
            window.removeEventListener('mouseup', handleMouseUp);
            window.removeEventListener('resize', handleResize);
            window.removeEventListener('touchmove', handleTouchMove);
            window.removeEventListener('touchstart', handleTouchStart);
            window.removeEventListener('touchend', handleMouseUp);
        };
    }, []);

    return (
        <canvas
            ref={canvasRef}
            className="absolute inset-0 block h-full w-full pointer-events-auto"
            style={{ touchAction: 'none' }}
        />
    );
}
