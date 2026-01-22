<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Surat Pernyataan Ikatan Dinas</title>
    <style>
        body { font-family: serif; font-size: 12px; }
    </style>
</head>
<body>

<h3 style="text-align:center;">SURAT PERNYATAAN IKATAN DINAS</h3>

<p>Saya yang bertanda tangan di bawah ini:</p>

<table>
    <tr>
        <td>Nama</td>
        <td>: {{ data_get($payload, 'employee.nama') }}</td>
    </tr>
    <tr>
        <td>NIK</td>
        <td>: {{ data_get($payload, 'employee.nik') }}</td>
    </tr>
    <tr>
        <td>Jabatan</td>
        <td>: {{ data_get($payload, 'employee.jabatan') }}</td>
    </tr>
</table>

<p>
Bahwa saya akan mengikuti program:
<b>{{ data_get($payload, 'training.judul') }}</b>
di {{ data_get($payload, 'training.tempat') }}.
</p>

<p>
Apabila saya mengundurkan diri sebelum masa ikatan berakhir, saya
bersedia mengganti biaya sebesar
<b>Rp {{ number_format(data_get($payload, 'training.biaya')) }}</b>.
</p>

</body>
</html>
