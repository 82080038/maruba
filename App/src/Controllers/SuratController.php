<?php
namespace App\Controllers;
use App\Helpers\AuthHelper;

class SuratController
{
    public function index(): void
    {
        require_login();
        $title = 'Surat-Surat Koperasi';
        include view_path('surat/index', 'layout_admin');
    }

    public function lamaranKerja(): void
    {
        require_login();
        // AuthHelper::requirePermission('documents', 'download'); // Temporarily disabled
        $this->generatePDF('lamaran_kerja', 'Surat Lamaran Kerja');
    }

    public function permohonanAnggota(): void
    {
        require_login();
        // AuthHelper::requirePermission('documents', 'download'); // Temporarily disabled
        $this->generatePDF('permohonan_anggota', 'Surat Permohonan Menjadi Anggota');
    }

    public function daftarSah(): void
    {
        require_login();
        // AuthHelper::requirePermission('documents', 'download'); // Temporarily disabled
        $this->generatePDF('daftar_sah', 'Daftar Sah Anggota');
    }

    public function permohonanPinjaman(): void
    {
        require_login();
        // AuthHelper::requirePermission('documents', 'download'); // Temporarily disabled
        $this->generatePDF('permohonan_pinjaman', 'Surat Permohonan Pinjaman Dana');
    }

    public function skb(): void
    {
        require_login();
        // AuthHelper::requirePermission('documents', 'download'); // Temporarily disabled
        $this->generatePDF('skb', 'Surat Kesepakatan Bersama');
    }

    private function generatePDF($type, $title): void
    {
        // Load TCPDF jika ada, fallback ke HTML
        $tcpdfPath = __DIR__ . '/../../vendor/tecnickcom/tcpdf/tcpdf.php';
        
        if (file_exists($tcpdfPath)) {
            require_once $tcpdfPath;
            $pdf = new \TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
            
            // Set document info
            $pdf->SetCreator('KSP Lam Gabe Jaya');
            $pdf->SetAuthor('KSP Lam Gabe Jaya');
            $pdf->SetTitle($title);
            
            // Add a page
            $pdf->AddPage();
            
            // Set content
            $content = $this->getSuratContent($type);
            $pdf->writeHTML($content, true, false, true, false, '');
            
            // Close and output PDF document
            $pdf->Output($title . '.pdf', 'D');
        } else {
            // Fallback: Output sebagai HTML dengan print stylesheet
            $content = $this->getSuratContent($type);
            include view_path('surat/print_layout');
        }
    }

    private function getSuratContent($type): string
    {
        switch ($type) {
            case 'lamaran_kerja':
                return $this->getLamaranKerjaContent();
            case 'permohonan_anggota':
                return $this->getPermohonanAnggotaContent();
            case 'daftar_sah':
                return $this->getDaftarSahContent();
            case 'permohonan_pinjaman':
                return $this->getPermohonanPinjamanContent();
            case 'skb':
                return $this->getSKBContent();
            default:
                return '';
        }
    }

    private function getLamaranKerjaContent(): string
    {
        return '
        <div style="font-family: Times New Roman; line-height: 1.6;">
            <p style="text-align: right;">Pangururan, ' . date('d F Y') . '</p>
            
            <p style="text-align: center; font-weight: bold;">SURAT LAMARAN KERJA</p>
            <br>
            
            <p>Kepada Yth. Pengurus KSP Lam Gabe Jaya<br>di Tempat</p>
            
            <p><strong>Hal: Lamaran Kerja</strong></p>
            <br>
            
            <p>Saya yang bertanda tangan di bawah ini:</p>
            <table style="border: none; width: 100%;">
                <tr><td style="width: 150px;">Nama</td><td>: ……………………………</td></tr>
                <tr><td>Tempat/Tgl lahir</td><td>: ……………………………</td></tr>
                <tr><td>Alamat</td><td>: ……………………………</td></tr>
                <tr><td>No. HP</td><td>: ……………………………</td></tr>
                <tr><td>Pendidikan terakhir</td><td>: ……………………………</td></tr>
                <tr><td>Posisi yang dilamar</td><td>: ……………………………</td></tr>
            </table>
            <br>
            
            <p>Dengan ini mengajukan lamaran kerja pada KSP Lam Gabe Jaya. Sebagai bahan pertimbangan, saya lampirkan:</p>
            <ol>
                <li>Fotokopi KTP</li>
                <li>CV/Daftar Riwayat Hidup</li>
                <li>Ijazah & Transkrip</li>
                <li>SKCK (bila ada)</li>
                <li>Sertifikat pendukung (bila ada)</li>
            </ol>
            <br>
            
            <p>Demikian permohonan ini saya ajukan. Atas perhatian Bapak/Ibu, saya ucapkan terima kasih.</p>
            <br><br><br><br>
            
            <div style="text-align: center;">
                <p>Hormat saya,</p>
                <br><br><br>
                <p>………………………………</p>
            </div>
        </div>';
    }

    private function getPermohonanAnggotaContent(): string
    {
        return '
        <div style="font-family: Times New Roman; line-height: 1.6;">
            <p style="text-align: right;">Pangururan, ' . date('d F Y') . '</p>
            
            <p style="text-align: center; font-weight: bold;">SURAT PERMOHONAN MENJADI ANGGOTA</p>
            <br>
            
            <p>Kepada Yth. Pengurus KSP Lam Gabe Jaya<br>di Tempat</p>
            
            <p><strong>Hal: Permohonan Menjadi Anggota</strong></p>
            <br>
            
            <p>Saya yang bertanda tangan di bawah ini:</p>
            <table style="border: none; width: 100%;">
                <tr><td style="width: 150px;">Nama</td><td>: ……………………………</td></tr>
                <tr><td>No. KTP</td><td>: ……………………………</td></tr>
                <tr><td>Alamat</td><td>: ……………………………</td></tr>
                <tr><td>No. HP</td><td>: ……………………………</td></tr>
                <tr><td>Pekerjaan</td><td>: ……………………………</td></tr>
            </table>
            <br>
            
            <p>Mengajukan permohonan menjadi anggota KSP Lam Gabe Jaya dan bersedia:</p>
            <ul>
                <li>Mematuhi AD/ART dan peraturan koperasi.</li>
                <li>Membayar simpanan pokok, wajib, dan ketentuan lain yang berlaku.</li>
                <li>Mengikuti pendidikan dasar koperasi.</li>
            </ul>
            
            <p><strong>Lampiran:</strong></p>
            <ol>
                <li>Fotokopi KTP</li>
                <li>Pas foto 3x4 … lembar</li>
                <li>Formulir data anggota (jika ada)</li>
            </ol>
            <br>
            
            <p>Demikian permohonan ini saya sampaikan. Terima kasih.</p>
            <br><br><br><br>
            
            <div style="text-align: center;">
                <p>Pemohon,</p>
                <br><br><br>
                <p>………………………………</p>
            </div>
        </div>';
    }

    private function getDaftarSahContent(): string
    {
        return '
        <div style="font-family: Times New Roman; line-height: 1.6;">
            <p style="text-align: center; font-weight: bold;">KOPERASI SIMPAN PINJAM LAM GABE JAYA</p>
            <p style="text-align: center; font-weight: bold;">DAFTAR SAH ANGGOTA</p>
            <p style="text-align: center;">Per ' . date('d F Y') . '</p>
            <br>
            
            <table style="border: 1px solid #000; border-collapse: collapse; width: 100%;">
                <tr style="background-color: #f0f0f0;">
                    <th style="border: 1px solid #000; padding: 5px;">No</th>
                    <th style="border: 1px solid #000; padding: 5px;">Nama Anggota</th>
                    <th style="border: 1px solid #000; padding: 5px;">No. Anggota</th>
                    <th style="border: 1px solid #000; padding: 5px;">No. KTP</th>
                    <th style="border: 1px solid #000; padding: 5px;">Alamat</th>
                    <th style="border: 1px solid #000; padding: 5px;">Tanggal Bergabung</th>
                    <th style="border: 1px solid #000; padding: 5px;">Tanda Tangan</th>
                </tr>';
        
        // Tambahkan baris kosong untuk contoh
        for ($i = 1; $i <= 10; $i++) {
            $content .= '
                <tr>
                    <td style="border: 1px solid #000; padding: 5px; text-align: center;">' . $i . '</td>
                    <td style="border: 1px solid #000; padding: 5px;"></td>
                    <td style="border: 1px solid #000; padding: 5px;"></td>
                    <td style="border: 1px solid #000; padding: 5px;"></td>
                    <td style="border: 1px solid #000; padding: 5px;"></td>
                    <td style="border: 1px solid #000; padding: 5px;"></td>
                    <td style="border: 1px solid #000; padding: 5px;"></td>
                </tr>';
        }
        
        $content .= '
            </table>
            <br><br>
            
            <div style="display: flex; justify-content: space-between;">
                <div style="text-align: center;">
                    <p>Mengetahui,</p>
                    <p><strong>Ketua Pengurus</strong></p>
                    <br><br><br>
                    <p>………………….</p>
                </div>
                <div style="text-align: center;">
                    <p><strong>Sekretaris</strong></p>
                    <br><br><br>
                    <p>………………….</p>
                </div>
            </div>
        </div>';
        
        return $content;
    }

    private function getPermohonanPinjamanContent(): string
    {
        return '
        <div style="font-family: Times New Roman; line-height: 1.6;">
            <p style="text-align: right;">Pangururan, ' . date('d F Y') . '</p>
            
            <p style="text-align: center; font-weight: bold;">SURAT PERMOHONAN PINJAMAN DANA</p>
            <br>
            
            <p>Kepada Yth. Pengurus KSP Lam Gabe Jaya<br>di Tempat</p>
            
            <p><strong>Hal: Permohonan Pinjaman Dana</strong></p>
            <br>
            
            <p>Saya yang bertanda tangan di bawah ini:</p>
            <table style="border: none; width: 100%;">
                <tr><td style="width: 180px;">Nama</td><td>: ……………………………</td></tr>
                <tr><td>No. Anggota (jika anggota)</td><td>: ……………………………</td></tr>
                <tr><td>No. KTP</td><td>: ……………………………</td></tr>
                <tr><td>Alamat</td><td>: ……………………………</td></tr>
                <tr><td>No. HP</td><td>: ……………………………</td></tr>
                <tr><td>Pekerjaan</td><td>: ……………………………</td></tr>
            </table>
            <br>
            
            <p>Dengan ini mengajukan pinjaman sebesar Rp …………… (… rupiah) untuk keperluan ………………… dengan jangka waktu ……… bulan.</p>
            <br>
            
            <p><strong>Kesanggupan:</strong></p>
            <ul>
                <li>Membayar angsuran pokok dan jasa sesuai jadwal yang ditetapkan koperasi.</li>
                <li>Menyediakan agunan (jika disyaratkan): ……………………………</li>
            </ul>
            
            <p><strong>Lampiran:</strong></p>
            <ol>
                <li>Fotokopi KTP</li>
                <li>Kartu anggota (bagi anggota)</li>
                <li>Slip gaji/usaha (bila ada)</li>
                <li>Data agunan (bila ada)</li>
            </ol>
            <br>
            
            <p>Demikian permohonan ini saya sampaikan. Terima kasih.</p>
            <br><br><br><br>
            
            <div style="text-align: center;">
                <p>Pemohon,</p>
                <br><br><br>
                <p>………………………………</p>
            </div>
        </div>';
    }

    private function getSKBContent(): string
    {
        return '
        <div style="font-family: Times New Roman; line-height: 1.6;">
            <p style="text-align: center; font-weight: bold;">SURAT KESEPAKATAN BERSAMA</p>
            <p style="text-align: center;">No: ……………/SKB/KSP-LGJ/' . date('Y') . '</p>
            <br>
            
            <p>Pada hari ini …………… tanggal …………… bertempat di ……………, kami yang bertanda tangan di bawah ini:</p>
            <br>
            
            <p><strong>1. Nama       : ……………………………</strong> (perwakilan KSP Lam Gabe Jaya)<br>
               Jabatan    : ……………………………<br>
               Alamat KSP : Jl. Pulo Samosir, Pangururan, Samosir</p>
            <br>
            
            <p><strong>2. Nama       : ……………………………</strong> (Nasabah/Peminjam)<br>
               No. KTP    : ……………………………<br>
               Alamat     : ……………………………<br>
               No. HP     : ……………………………</p>
            <br>
            
            <p>Menyatakan sepakat atas pinjaman sebesar Rp …………… (… rupiah) dengan ketentuan:</p>
            <ol>
                <li>Jangka waktu: ……… bulan; jadwal angsuran: ……………</li>
                <li>Jasa/bunga: ……… % per ……… (sesuai kebijakan koperasi).</li>
                <li>Agunan (jika ada): ……………………………</li>
                <li>Keterlambatan/wanprestasi akan dikenakan sanksi sesuai peraturan koperasi.</li>
                <li>Hal-hal lain yang belum diatur akan disepakati kemudian secara tertulis.</li>
            </ol>
            <br>
            
            <p>Demikian SKB ini dibuat untuk dipatuhi kedua belah pihak.</p>
            <br><br><br><br>
            
            <div style="display: flex; justify-content: space-between;">
                <div style="text-align: center;">
                    <p><strong>Pihak Koperasi,</strong></p>
                    <br><br><br>
                    <p>………………………………</p>
                    <p>Jabatan: ………………………</p>
                </div>
                <div style="text-align: center;">
                    <p><strong>Peminjam/Nasabah,</strong></p>
                    <br><br><br>
                    <p>………………………………</p>
                </div>
            </div>
        </div>';
    }
}
