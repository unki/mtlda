<table>
 <tr>
  <th>Idx</th>
  <th>Filename</th>
  <th>Hash</th>
  <th>Size</th>
  <th>State</th>
  <th>Time</th>
 </tr>
{queue_list}
 <tr>
  <td><a href="{get_url page=queue mode=edit id=$item_safe_link}">{$item->queue_idx}</a></td>
  <td><a href="{get_url page=queue mode=edit id=$item_safe_link}">{$item->queue_file_name}</a></td>
  <td>{$item->queue_file_hash}</td>
  <td>{$item->queue_file_size}</td>
  <td>{$item->queue_state}</td>
  <td>{$item->queue_time}</td>
 </tr>
{/queue_list}
