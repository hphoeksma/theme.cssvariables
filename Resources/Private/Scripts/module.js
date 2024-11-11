import ReactDOM from 'react-dom'
import React, { useEffect, useState, useRef } from 'react'
import {SketchPicker} from 'react-color'

const ColorPicker = ({ color, targetId }) => {
	const [selectedColor, setSelectedColor] = useState(color)
	const [isPickerVisible, setIsPickerVisible] = useState(false)
	const pickerRef = useRef(null)

	const handleColorChange = color => {
		setSelectedColor(color.hex)
		const inputElement = document.querySelector(targetId)
		if (inputElement) {
			inputElement.value = color.hex

			// Trigger an 'input' event to ensure the value change is registered
			const event = new Event('input', { bubbles: true })
			inputElement.dispatchEvent(event)
		}
	}

	const togglePicker = () => {
		setIsPickerVisible(!isPickerVisible)
	}

	// Close picker if clicking outside
	useEffect(() => {
		const handleClickOutside = event => {
			if (pickerRef.current && !pickerRef.current.contains(event.target)) {
				setIsPickerVisible(false)
			}
		}

		document.addEventListener('mousedown', handleClickOutside)
		return () => document.removeEventListener('mousedown', handleClickOutside)
	}, [])

	return (
		<div>
			{/* Color preview square */}
			<div
				style={{
					width: '20px',
					height: '20px',
					backgroundColor: selectedColor,
					cursor: 'pointer',
					border: '1px solid #ddd',
					borderRadius: '10px',
					display: 'inline-block'
				}}
				onClick={togglePicker}
			/>

			{/* Color picker, only visible on click */}
			{isPickerVisible && (
				<div ref={pickerRef} style={{ position: 'absolute', zIndex: '2', marginLeft: '-74px', marginTop: '8px' }}>
					<SketchPicker color={selectedColor} onChange={handleColorChange} />
				</div>
			)}
		</div>
	)
}

window.addEventListener('DOMContentLoaded', () => {
	document.querySelectorAll('.react__placeholder--color-picker').forEach(el => {
		const color = el.getAttribute('data-color')
		const targetId = el.getAttribute('data-target')
		ReactDOM.render(<ColorPicker color={color} targetId={targetId}/>, el)
	})
})
