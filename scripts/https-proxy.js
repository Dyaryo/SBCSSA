// ---------------------------------------------------------------------------
//  Minimal HTTPS terminator for the LAN.
//
//  The Laravel app runs on http://127.0.0.1:8000 (loopback only). This proxy
//  accepts TLS from the terminals on port 8443, forwards X-Forwarded-* headers
//  so Laravel knows the request was HTTPS, and keeps the app itself off the
//  plain-HTTP interface.
//
//  No npm packages required — Node built-ins only. This project's package.json
//  declares "type": "module", so the file uses ES module imports.
//  Start it with scripts\start-https.bat
// ---------------------------------------------------------------------------
import https from 'node:https';
import http from 'node:http';
import fs from 'node:fs';
import path from 'node:path';
import os from 'node:os';
import { fileURLToPath } from 'node:url';

const here = path.dirname(fileURLToPath(import.meta.url));
const ROOT = path.join(here, '..');
const CERT = path.join(ROOT, 'storage', 'certs', 'server.crt');
const KEY = path.join(ROOT, 'storage', 'certs', 'server.key');

const TARGET_HOST = '127.0.0.1';
const TARGET_PORT = Number(process.env.APP_PORT || 8000);
const LISTEN_PORT = Number(process.env.HTTPS_PORT || 8443);

if (!fs.existsSync(CERT) || !fs.existsSync(KEY)) {
    console.error('Certificate missing. Run scripts\\make-certs.bat first.');
    process.exit(1);
}

const server = https.createServer(
    {
        key: fs.readFileSync(KEY),
        cert: fs.readFileSync(CERT),
        minVersion: 'TLSv1.2'
    },
    (req, res) => {
        const remote = (req.socket.remoteAddress || '').replace(/^::ffff:/, '');
        const chain = req.headers['x-forwarded-for']
            ? remote + ', ' + req.headers['x-forwarded-for']
            : remote;

        const headers = {
            ...req.headers,
            host: TARGET_HOST + ':' + TARGET_PORT,
            'x-forwarded-for': chain,
            'x-forwarded-proto': 'https',
            'x-forwarded-port': String(LISTEN_PORT),
            'x-forwarded-host': req.headers.host || ''
        };

        const upstream = http.request(
            {
                host: TARGET_HOST,
                port: TARGET_PORT,
                method: req.method,
                path: req.url,
                headers: headers
            },
            (up) => {
                res.writeHead(up.statusCode || 502, {
                    ...up.headers,
                    'strict-transport-security': 'max-age=31536000'
                });
                up.pipe(res);
            }
        );

        upstream.on('error', (err) => {
            if (!res.headersSent) {
                res.writeHead(502, { 'content-type': 'text/plain; charset=utf-8' });
            }
            res.end('Application not reachable on ' + TARGET_HOST + ':' + TARGET_PORT + ' — ' + err.message);
        });

        req.pipe(upstream);
    }
);

server.on('tlsClientError', () => {
    // self-signed / untrusted client warnings are noise; ignore them
});

server.listen(LISTEN_PORT, '0.0.0.0', () => {
    const addresses = [];
    Object.values(os.networkInterfaces()).forEach((list) => {
        (list || []).forEach((item) => {
            if (item.family === 'IPv4' && !item.internal) {
                addresses.push(item.address);
            }
        });
    });

    console.log('HTTPS terminator running');
    console.log('  local : https://localhost:' + LISTEN_PORT);
    addresses.forEach((ip) => console.log('  LAN   : https://' + ip + ':' + LISTEN_PORT));
    console.log('  app   : http://' + TARGET_HOST + ':' + TARGET_PORT + ' (loopback only)');
});
