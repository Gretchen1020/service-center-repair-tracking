<?php
// Shared partial: renders <tr> rows for the customer table.
// Expects $customers (array of rows with id, name, mobile, address) to be set
// by whichever page includes this file (customers.php or ajax/search_customers.php).

if (empty($customers)):
?>
    <!-- no rows; "No customers found" message is shown by the including page -->
<?php
else:
    foreach ($customers as $c):
?>
    <tr>
        <td data-label="Name"><?= htmlspecialchars($c['name']) ?></td>
        <td data-label="Mobile"><?= htmlspecialchars($c['mobile']) ?></td>
        <td data-label="Address"><?= htmlspecialchars($c['address'] ?? '') ?></td>
        <td>
            <a href="customers.php?edit=<?= (int) $c['id'] ?>" class="btn">Edit</a>
            <form method="POST" action="customers.php" class="inline-form"
                  onsubmit="return confirm('Delete <?= htmlspecialchars(addslashes($c['name'])) ?>? This cannot be undone.');">
                <input type="hidden" name="action" value="delete">
                <input type="hidden" name="delete_id" value="<?= (int) $c['id'] ?>">
                <button type="submit" class="btn btn-danger">Delete</button>
            </form>
        </td>
    </tr>
<?php
    endforeach;
endif;