<?php
header("Content-Type: application/json");
session_start();
include 'db_connect.php';

$method = $_SERVER['REQUEST_METHOD'];
$request = explode('/', trim($_SERVER['PATH_INFO'],'/'));
$input = json_decode(file_get_contents('php://input'), true);

$action = $request[0] ?? null;

switch ($action) {
    case 'register':
        handle_register($conn, $input);
        break;
    case 'login':
        handle_login($conn, $input);
        break;
    case 'logout':
        handle_logout();
        break;
    case 'check_auth':
        check_auth_status();
        break;
    case 'products':
        get_products($conn);
        break;
    case 'cart':
        handle_cart($conn, $method, $input);
        break;
    case 'checkout':
        handle_checkout($conn);
        break;
    case 'profile': // New endpoint for profile
        handle_profile($conn, $method, $input);
        break;
    case 'orders': // New endpoint for orders
        get_orders($conn);
        break;
    default:
        echo json_encode(['status' => 'error', 'message' => 'Invalid endpoint']);
        break;
}

function handle_register($conn, $data) {
    $name = $data['name'] ?? '';
    $email = $data['email'] ?? '';
    $password = $data['password'] ?? '';
    $phone = $data['phone'] ?? '';

    if (empty($name) || empty($email) || empty($password)) {
        echo json_encode(['status' => 'error', 'message' => 'Please fill all required fields.']);
        return;
    }

    $hashed_password = password_hash($password, PASSWORD_DEFAULT);

    $stmt = $conn->prepare("INSERT INTO users (name, email, password, phone) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("ssss", $name, $email, $hashed_password, $phone);

    if ($stmt->execute()) {
        echo json_encode(['status' => 'success', 'message' => 'Registration successful.']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Registration failed. Email might already exist.']);
    }
    $stmt->close();
}

function handle_login($conn, $data) {
    $email = $data['email'] ?? '';
    $password = $data['password'] ?? '';

    $stmt = $conn->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($user = $result->fetch_assoc()) {
        if (password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_name'] = $user['name'];
            $_SESSION['user_email'] = $user['email'];
            echo json_encode([
                'status' => 'success', 
                'user' => [
                    'id' => $user['id'],
                    'name' => $user['name'],
                    'email' => $user['email']
                ]
            ]);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Invalid password.']);
        }
    } else {
        echo json_encode(['status' => 'error', 'message' => 'No user found with that email.']);
    }
    $stmt->close();
}

function handle_logout() {
    session_destroy();
    echo json_encode(['status' => 'success', 'message' => 'Logged out successfully.']);
}

function check_auth_status() {
    if (isset($_SESSION['user_id'])) {
        echo json_encode([
            'loggedIn' => true,
            'user' => [
                'id' => $_SESSION['user_id'],
                'name' => $_SESSION['user_name'],
                'email' => $_SESSION['user_email']
            ]
        ]);
    } else {
        echo json_encode(['loggedIn' => false]);
    }
}

function get_products($conn) {
    // UPDATED: Selecting image_url instead of icon
    $result = $conn->query("SELECT id, name, price, category, image_url FROM products");
    $products = [];
    while ($row = $result->fetch_assoc()) {
        $products[] = $row;
    }
    echo json_encode($products);
}

function handle_cart($conn, $method, $data) {
    if (!isset($_SESSION['user_id'])) {
        echo json_encode(['status' => 'error', 'message' => 'You must be logged in.']);
        return;
    }
    $user_id = $_SESSION['user_id'];

    if ($method === 'GET') {
        $stmt = $conn->prepare("SELECT p.id, p.name, p.price, c.quantity FROM cart c JOIN products p ON c.product_id = p.id WHERE c.user_id = ?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $cart_items = [];
        while ($row = $result->fetch_assoc()) {
            $cart_items[] = $row;
        }
        echo json_encode($cart_items);
        $stmt->close();
    } elseif ($method === 'POST') {
        $product_id = $data['productId'];
        $quantity_change = $data['quantityChange'] ?? 1;

        $stmt = $conn->prepare("SELECT * FROM cart WHERE user_id = ? AND product_id = ?");
        $stmt->bind_param("ii", $user_id, $product_id);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $stmt = $conn->prepare("UPDATE cart SET quantity = quantity + ? WHERE user_id = ? AND product_id = ?");
            $stmt->bind_param("iii", $quantity_change, $user_id, $product_id);
        } else {
            $stmt = $conn->prepare("INSERT INTO cart (user_id, product_id, quantity) VALUES (?, ?, ?)");
            $stmt->bind_param("iii", $user_id, $product_id, $quantity_change);
        }
        $stmt->execute();

        $conn->query("DELETE FROM cart WHERE user_id = $user_id AND quantity <= 0");
        
        echo json_encode(['status' => 'success', 'message' => 'Cart updated.']);
        $stmt->close();
    } elseif ($method === 'DELETE') {
        $product_id = $data['productId'];
        $stmt = $conn->prepare("DELETE FROM cart WHERE user_id = ? AND product_id = ?");
        $stmt->bind_param("ii", $user_id, $product_id);
        $stmt->execute();
        echo json_encode(['status' => 'success', 'message' => 'Item removed from cart.']);
        $stmt->close();
    }
}

function handle_checkout($conn) {
    if (!isset($_SESSION['user_id'])) {
        echo json_encode(['status' => 'error', 'message' => 'You must be logged in.']);
        return;
    }
    $user_id = $_SESSION['user_id'];

    $stmt = $conn->prepare("SELECT p.id as product_id, p.price, c.quantity FROM cart c JOIN products p ON c.product_id = p.id WHERE c.user_id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $cart_items = [];
    $total = 0;
    while ($row = $result->fetch_assoc()) {
        $cart_items[] = $row;
        $total += $row['price'] * $row['quantity'];
    }
    $stmt->close();

    if (empty($cart_items)) {
        echo json_encode(['status' => 'error', 'message' => 'Cart is empty.']);
        return;
    }

    $stmt = $conn->prepare("INSERT INTO orders (user_id, total) VALUES (?, ?)");
    $stmt->bind_param("id", $user_id, $total);
    $stmt->execute();
    $order_id = $stmt->insert_id;
    $stmt->close();

    $stmt = $conn->prepare("INSERT INTO order_items (order_id, product_id, quantity, price) VALUES (?, ?, ?, ?)");
    foreach ($cart_items as $item) {
        $stmt->bind_param("iiid", $order_id, $item['product_id'], $item['quantity'], $item['price']);
        $stmt->execute();
    }
    $stmt->close();

    $stmt = $conn->prepare("DELETE FROM cart WHERE user_id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $stmt->close();

    echo json_encode(['status' => 'success', 'message' => 'Order placed successfully.', 'orderId' => $order_id]);
}

function handle_profile($conn, $method, $input) {
    if (!isset($_SESSION['user_id'])) {
        echo json_encode(['status' => 'error', 'message' => 'You must be logged in.']);
        return;
    }
    $user_id = $_SESSION['user_id'];

    if ($method === 'GET') {
        $stmt = $conn->prepare("SELECT name, email, phone, birthday FROM users WHERE id = ?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($user = $result->fetch_assoc()) {
            echo json_encode(['status' => 'success', 'user' => $user]);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'User not found.']);
        }
        $stmt->close();
    } elseif ($method === 'POST') {
        $name = $input['name'] ?? '';
        $email = $input['email'] ?? '';
        $phone = $input['phone'] ?? '';
        $birthday = $input['birthday'] ?? null;
        
        if (empty($name) || empty($email)) {
            echo json_encode(['status' => 'error', 'message' => 'Name and Email are required.']);
            return;
        }
        
        if ($birthday === '') {
            $birthday = null;
        }

        $stmt = $conn->prepare("UPDATE users SET name = ?, email = ?, phone = ?, birthday = ? WHERE id = ?");
        $stmt->bind_param("ssssi", $name, $email, $phone, $birthday, $user_id);
        
        if ($stmt->execute()) {
            $_SESSION['user_name'] = $name;
            $_SESSION['user_email'] = $email;
            echo json_encode(['status' => 'success', 'message' => 'Profile updated successfully.', 'user' => ['name' => $name, 'email' => $email]]);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Failed to update profile. Email might be in use.']);
        }
        $stmt->close();
    }
}

function get_orders($conn) {
    if (!isset($_SESSION['user_id'])) {
        echo json_encode(['status' => 'error', 'message' => 'You must be logged in.']);
        return;
    }
    $user_id = $_SESSION['user_id'];

    $stmt = $conn->prepare("
        SELECT 
            o.id, o.total, o.created_at, 
            GROUP_CONCAT(CONCAT_WS('::', p.name, oi.quantity, oi.price) SEPARATOR '||') as items
        FROM orders o
        JOIN order_items oi ON o.id = oi.order_id
        JOIN products p ON oi.product_id = p.id
        WHERE o.user_id = ?
        GROUP BY o.id
        ORDER BY o.created_at DESC
    ");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $orders = [];
    while($row = $result->fetch_assoc()) {
        $items_str = $row['items'];
        $items_arr = explode('||', $items_str);
        $items = [];
        foreach ($items_arr as $item_str) {
            list($name, $quantity, $price) = explode('::', $item_str);
            $items[] = ['name' => $name, 'quantity' => (int)$quantity, 'price' => (float)$price];
        }
        $row['items'] = $items;
        $orders[] = $row;
    }
    
    echo json_encode($orders);
    $stmt->close();
}


$conn->close();
?>