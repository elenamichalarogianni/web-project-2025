<div class="form-container">
  <h1>Δημιουργία Νέου Θέματος</h1>

  <form id="topicForm" method="post" action="insert_topic.php" enctype="multipart/form-data">
    <input type="text" name="title" placeholder="Enter Thesis Title"/>

    <textarea name="description" placeholder="Enter Description"></textarea>

  <div class="pdf-inputs">
    <input type="url" id="pdfpath" name="pdfpath" placeholder="Enter PDF path (π.χ. /files/thesis1.pdf)">

    <div class="file-upload">
      <label for="pdfFile" class="btn-file">Choose File</label>
      <span class="file-name" id="pdfFileName">No file chosen</span>
      <input type="file" id="pdfFile" name="pdfFile" accept=".pdf" class="file-input">
    </div>
  </div>


    <button type="submit" name="submit">Submit</button>
  </form>
</div>

<div class="topics-list">
  <h2>Θέματα Προς Ανάθεση</h2>
  <ul id="topicsList"></ul>
</div>

<div id="editOverlay" class="modal-overlay" style="display:none;">
  <div class="modal" role="dialog" aria-modal="true" aria-labelledby="editTitle">
    <div class="modal-header">
      <span>Επεξεργασία Θέματος</span>
    </div>

    <form id="editForm" class="modal-body" method="post" action="update_topic.php" enctype="multipart/form-data">
      <input type="hidden" name="id" id="editId">

      <input type="text" name="title" id="editTitle" placeholder="Τίτλος" class="modal-input">

      <textarea name="description" id="editDesc" placeholder="Σύνοψη" class="modal-textarea"></textarea>

      <input type="url" name="pdfpath" id="editPdf" placeholder="/files/thesis1.pdf" class="modal-input">

      <div class="file-upload">
        <label for="editPdfFile" class="btn-file">Choose File</label>
        <span class="file-name" id="editPdfFileName">No file chosen</span>
        <input type="file" id="editPdfFile" name="pdfFile" accept=".pdf" class="file-input">
      </div>

      <div class="modal-actions">
        <button type="button" id="editSave" class="btn btn-primary">Save</button>
        <button type="button" id="editCancel" class="btn btn-secondary">Cancel</button>
      </div>
    </form>
  </div>
</div>



